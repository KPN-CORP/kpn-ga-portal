<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\BisnisUnit;

class Voucher extends Model
{
    protected $table = 'drms_vouchers'; // perbaiki jika masih 'vouchers'
    protected $fillable = ['code', 'nominal', 'type', 'status', 'expired_at', 'business_unit_id', 'area', 'input_business_unit_id'];

    protected $casts = [
        'type' => 'string',
        'status' => 'string',
        'expired_at' => 'date',
    ];

    /**
     * Voucher dianggap expired jika tanggal expired sudah LEWAT — bukan pas hari-H.
     * expired_at dicasting jadi tanggal (jam 00:00:00), jadi harus dicek sampai akhir
     * hari (23:59:59) dulu baru dianggap kadaluarsa; voucher masih boleh dipakai
     * sepanjang hari tanggal expired-nya, baru expired mulai keesokan harinya.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expired_at !== null && $this->expired_at->copy()->endOfDay()->isPast();
    }


    /**
     * Cakupan voucher yang boleh DILIHAT/DIKELOLA oleh admin (list, edit, hapus).
     * - Superadmin           : semua BU, semua area.
     * - Admin tanpa area     : semua area di dalam BU-nya (lintas area).
     * - Admin 1..n area      : hanya area-area yang dipegangnya (drms_admin_areas) di dalam BU-nya.
     */
    public function scopeManageableBy($query, $user)
    {
        if ($user->isDrmsSuperAdmin()) {
            return $query;
        }
        $profile = $user->drmsProfile;
        $query->where('business_unit_id', $profile->business_unit_id ?? 0);
        $areas = $profile ? $profile->adminAreaList() : [];
        if (!empty($areas)) {
            $query->whereIn('area', $areas);
        }
        return $query;
    }

    /**
     * Cakupan voucher yang boleh DIBERIKAN ke sebuah request.
     * Voucher harus satu area dengan pemohon, supaya area A tidak memberi voucher area B
     * walaupun admin-nya lintas area.
     * - Request hasil forward (current_business_unit_id terisi): area pemohon milik BU lain,
     *   jadi yang dipakai adalah cakupan area admin penerima.
     * - Request biasa: voucher.area harus sama dengan area pemohon (pemohon tanpa area
     *   -> hanya voucher tanpa area).
     */
    public function scopeAssignableTo($query, DriverRequest $driverRequest, $user, bool $showAllBu = false)
    {
        if ($user->isDrmsSuperAdmin()) {
            return $query;
        }

        $requesterProfile = $driverRequest->requester->drmsProfile ?? null;
        $isForwarded = !empty($driverRequest->current_business_unit_id);

        if (!$showAllBu) {
            $buId = $driverRequest->current_business_unit_id ?? ($requesterProfile->business_unit_id ?? 0);
            $query->where('business_unit_id', $buId);
        }

        if ($isForwarded) {
            $adminAreas = $user->drmsProfile ? $user->drmsProfile->adminAreaList() : [];
            if (!empty($adminAreas)) {
                $query->whereIn('area', $adminAreas);
            }
            return $query;
        }

        $area = $requesterProfile->area ?? null;
        if ($area) {
            $query->where('area', $area);
        } else {
            $query->where(fn ($q) => $q->whereNull('area')->orWhere('area', ''));
        }
        return $query;
    }

    public function canBeManagedBy($user): bool
    {
        if ($user->isDrmsSuperAdmin()) {
            return true;
        }
        $profile = $user->drmsProfile;
        if (!$profile || (int) $this->business_unit_id !== (int) $profile->business_unit_id) {
            return false;
        }
        return $profile->holdsArea($this->area);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'business_unit_id', 'id_bisnis_unit');
    }

    /**
     * Business unit tujuan/input tambahan yang dipilih saat membuat voucher.
     * Saat ini khusus dipakai oleh user dari business unit "KPN Corporation".
     */
    public function inputBusinessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'input_business_unit_id', 'id_bisnis_unit');
    }

    public function request()
    {
        return $this->hasOne(DriverRequest::class);
    }

    /**
     * Semua request yang pernah "menggunakan" voucher ini lewat tabel pivot
     * drms_request_vouchers (mendukung voucher tambahan, bukan cuma voucher utama
     * lewat kolom voucher_id di drms_requests). pivot->created_at mencatat kapan
     * voucher ini dipasangkan/diberikan ke request tsb.
     */
    public function usedByRequests()
    {
        return $this->belongsToMany(DriverRequest::class, 'drms_request_vouchers', 'voucher_id', 'request_id')
            ->withTimestamps();
    }

    /**
     * Info "siapa yang pakai & kapan diberikan" untuk voucher ini, dipakai di
     * halaman daftar voucher. Utamakan data dari tabel pivot (drms_request_vouchers,
     * yang terbaru kalau voucher pernah dipasang ulang), fallback ke relasi request()
     * lama (kolom voucher_id) untuk data lama sebelum tabel pivot ada.
     * Return null kalau voucher belum pernah diberikan ke request manapun.
     */
    public function getUsageInfoAttribute()
    {
        $viaPivot = $this->relationLoaded('usedByRequests')
            ? $this->usedByRequests
            : $this->usedByRequests()->get();

        $latest = $viaPivot->sortByDesc(fn ($r) => $r->pivot->created_at)->first();

        if ($latest) {
            return [
                'requester_name' => $latest->requester->name ?? '-',
                'given_at'       => $latest->pivot->created_at,
            ];
        }

        $legacyRequest = $this->relationLoaded('request') ? $this->request : $this->request()->first();

        if ($legacyRequest) {
            return [
                'requester_name' => $legacyRequest->requester->name ?? '-',
                'given_at'       => $legacyRequest->approved_admin_at ?? $legacyRequest->updated_at,
            ];
        }

        return null;
    }
}