<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class DrmsLogVerifier
{
    /**
     * Daftar ID kendaraan khusus yang boleh dilihat & diverifikasi user ini
     * (log BBM dan log perjalanan), walau kendaraannya beda BU.
     * Sumber: tabel drms_log_verifier_vehicles.
     * Kalau tabel belum ada / query gagal -> [] (perilaku lama, tidak ada hak tambahan).
     */
    public static function vehicleIds($user): array
    {
        if (!$user) {
            return [];
        }

        static $cache = [];

        if (!isset($cache[$user->id])) {
            try {
                $cache[$user->id] = DB::table('drms_log_verifier_vehicles')
                    ->where('user_id', $user->id)
                    ->pluck('vehicle_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
            } catch (\Throwable $e) {
                $cache[$user->id] = [];
            }
        }

        return $cache[$user->id];
    }

    /** Apakah user ini punya hak khusus untuk kendaraan tersebut? */
    public static function canVerifyVehicle($user, $vehicleId): bool
    {
        if (!$vehicleId) {
            return false;
        }

        return in_array((int) $vehicleId, self::vehicleIds($user), true);
    }
}