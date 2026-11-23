<?php

namespace App\Services\Drms;

use App\Models\Drms\DrmsAdminArea;
use App\Models\Drms\Driver;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\RequestLog;
use App\Models\Drms\RequestRelease;
use App\Models\Drms\Vehicle;
use App\Models\User;
use App\Notifications\SlotReleaseCancelledNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Aturan "Selesai hari ini" untuk trip multi-hari.
 *
 * - Driver atau admin melepas slot: driver (dan mobil, default ikut) bebas dari jam selesai
 *   sampai jam trip lanjut besok (default = start_time request). Satu hari = 00:00 sampai 23:59,
 *   jadi slot bisa melewati tengah malam.
 * - Pengecekan bentrok memakai DriverRequest::exemptReleased(): request lain boleh ditugaskan
 *   ke driver/mobil yang sama selama seluruh jadwalnya (plus jeda) berada di dalam satu slot.
 * - Membatalkan slot yang sudah dipakai request lain: request itu tidak dipindah otomatis,
 *   admin diberi notifikasi.
 */
class SlotService
{
    public static function createRelease(
        DriverRequest $req,
        User $by,
        string $role,
        string $freeFromTime,
        ?string $resumeTime,
        bool $vehicleReleased,
        bool $atHomeCity,
        ?string $note
    ): RequestRelease {
        if (!$req->canReleaseToday()) {
            throw new \InvalidArgumentException('Slot hanya bisa dilepas untuk trip multi-hari yang sedang berjalan dan belum hari terakhir.');
        }
        if ($req->releaseToday()) {
            throw new \InvalidArgumentException('Slot hari ini sudah dilepas. Batalkan dulu kalau ingin mengubahnya.');
        }

        $today = now()->startOfDay();
        $freeFrom = Carbon::parse($today->format('Y-m-d') . ' ' . $freeFromTime);

        if ($freeFrom->gt(now()->addMinutes(5))) {
            throw new \InvalidArgumentException('Jam selesai tidak boleh di masa depan.');
        }
        if ($today->equalTo($req->usage_date->copy()->startOfDay()) && $freeFrom->lt($req->scheduledStart())) {
            throw new \InvalidArgumentException('Jam selesai tidak boleh sebelum trip dimulai (' . $req->scheduledStart()->format('H:i') . ').');
        }

        $resume = $resumeTime ?: Carbon::parse($req->start_time)->format('H:i');
        $freeUntil = Carbon::parse($today->copy()->addDay()->format('Y-m-d') . ' ' . $resume);
        if ($freeUntil->lte($freeFrom)) {
            throw new \InvalidArgumentException('Jam lanjut besok tidak valid.');
        }

        return DB::transaction(function () use ($req, $by, $role, $today, $freeFrom, $freeUntil, $vehicleReleased, $atHomeCity, $note) {
            $release = RequestRelease::create([
                'request_id'       => $req->id,
                'release_date'     => $today->toDateString(),
                'free_from'        => $freeFrom,
                'free_until'       => $freeUntil,
                'vehicle_released' => $vehicleReleased,
                'at_home_city'     => $atHomeCity,
                'released_by'      => $by->id,
                'released_role'    => $role,
                'note'             => $note,
            ]);

            RequestLog::create([
                'request_id'  => $req->id,
                'action'      => 'release_slot',
                'from_status' => $req->status,
                'to_status'   => $req->status,
                'note'        => $note,
                'detail'      => 'Selesai hari ini oleh ' . $role . ': bebas ' . $freeFrom->format('d M H:i')
                    . ' sampai ' . $freeUntil->format('d M H:i')
                    . ($vehicleReleased ? ' (mobil ikut dilepas)' : ' (mobil tetap dibawa)')
                    . ($atHomeCity ? '' : ' | driver di luar kota, slot tidak ditawarkan ke request lain'),
                'user_id'     => $by->id,
            ]);

            return $release;
        });
    }

    /**
     * Batalkan slot. Kalau sudah ada request lain di dalam slot, kembalikan daftarnya
     * dan kirim notifikasi ke admin (tidak dipindah otomatis).
     */
    public static function cancelRelease(RequestRelease $release, User $by, ?string $reason = null): array
    {
        $req = $release->request;
        $release->update(['cancelled_at' => now(), 'cancelled_by' => $by->id]);

        $affected = self::requestsInside($release);

        RequestLog::create([
            'request_id'  => $req->id,
            'action'      => 'cancel_release_slot',
            'from_status' => $req->status,
            'to_status'   => $req->status,
            'note'        => $reason,
            'detail'      => 'Slot ' . $release->free_from->format('d M H:i') . ' sampai ' . $release->free_until->format('d M H:i') . ' dibatalkan.'
                . ($affected ? ' Request terdampak: ' . implode(', ', $affected) . '.' : ''),
            'user_id'     => $by->id,
        ]);

        if ($affected) {
            Notification::send(self::adminsFor($req), new SlotReleaseCancelledNotification($req, $affected));
        }

        return $affected;
    }

    /** Batalkan semua slot aktif sebuah request (mis. driver diganti atau dikembalikan ke pending). */
    public static function cancelAllFor(DriverRequest $req, User $by, string $reason): array
    {
        $affected = [];
        foreach ($req->releases()->active()->get() as $release) {
            $affected = array_merge($affected, self::cancelRelease($release, $by, $reason));
        }

        return array_values(array_unique($affected));
    }

    /** request_no request lain yang jadwalnya beririsan dengan slot dan memakai driver/mobil yang sama. */
    public static function requestsInside(RequestRelease $release): array
    {
        $req = $release->request;
        if (!$req->driver_id && !$req->vehicle_id) {
            return [];
        }
        $from = $release->free_from->format('Y-m-d H:i:s');
        $until = $release->free_until->format('Y-m-d H:i:s');

        return DriverRequest::query()
            ->where('id', '!=', $req->id)
            ->whereIn('status', ['approved_admin', 'pending_l1', 'approved_l1'])
            ->where(function ($q) use ($req, $release) {
                $q->where('driver_id', $req->driver_id);
                if ($release->vehicle_released && $req->vehicle_id) {
                    $q->orWhere('vehicle_id', $req->vehicle_id);
                }
            })
            ->whereRaw("CONCAT(usage_date, ' ', start_time) < ? AND CONCAT(COALESCE(return_date, usage_date), ' ', COALESCE(return_time, end_time)) > ?", [$until, $from])
            ->get()
            ->map(fn ($r) => $r->request_no ?? ('#' . $r->id))
            ->all();
    }

    /** Admin BU/area pemohon (aturan sama dengan notifikasi approve L1). */
    public static function adminsFor(DriverRequest $req)
    {
        $profile = optional($req->requester)->drmsProfile;
        $businessUnitId = $req->current_business_unit_id ?: optional($profile)->business_unit_id;
        $area = optional($profile)->area;

        return User::whereHas('drmsProfile', function ($q) use ($businessUnitId) {
            $q->where('is_drms_admin', true)->where('business_unit_id', $businessUnitId);
        })->when($area, function ($q) use ($area) {
            $q->where(function ($sub) use ($area) {
                $sub->whereNotIn('id', DrmsAdminArea::select('user_id'))
                    ->orWhereIn('id', DrmsAdminArea::where('area', $area)->select('user_id'));
            });
        })->get();
    }

    /**
     * Bebaskan driver/mobil (status available) HANYA kalau tidak ada request disetujui lain yang
     * sedang berjalan sekarang. Dipakai saat request selesai/diganti, supaya request pendek yang
     * selesai di dalam slot tidak membuat driver "available" padahal trip multi-harinya masih jalan.
     */
    public static function freeIfIdle(?int $driverId, ?int $vehicleId, int $exceptRequestId): void
    {
        $now = now()->format('Y-m-d H:i:s');
        $running = fn (string $column, int $id) => DriverRequest::where($column, $id)
            ->where('id', '!=', $exceptRequestId)
            ->where('status', 'approved_admin')
            ->whereNull('merged_into_id')
            ->whereRaw("CONCAT(usage_date, ' ', start_time) <= ? AND CONCAT(COALESCE(return_date, usage_date), ' ', COALESCE(return_time, end_time)) >= ?", [$now, $now])
            ->exists();

        if ($driverId && !$running('driver_id', $driverId)) {
            Driver::where('id', $driverId)->update(['status' => 'available']);
        }
        if ($vehicleId && !$running('vehicle_id', $vehicleId)) {
            Vehicle::where('id', $vehicleId)->update(['status' => 'available']);
        }
    }
}
