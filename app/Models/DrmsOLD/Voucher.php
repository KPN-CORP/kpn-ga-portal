<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\BisnisUnit;

class Voucher extends Model
{
    protected $table = 'drms_vouchers'; // perbaiki jika masih 'vouchers'
    protected $fillable = ['code', 'nominal', 'type', 'status', 'expired_at', 'business_unit_id', 'input_business_unit_id'];

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