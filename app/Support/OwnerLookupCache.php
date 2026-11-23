<?php

namespace App\Support;

use App\Models\Drms\AssetVehicle;
use Illuminate\Support\Facades\DB;

/**
 * Cache in-memory (per request) untuk data "owner" (pemilik aset dari AMS)
 * berdasarkan plat nomor. Dipakai supaya menambahkan blok "owner" ke setiap
 * item di endpoint list (service-schedules, repairs, fuel-logs, trip-logs,
 * operational-summary) TIDAK menimbulkan 1 query per baris (N+1) — cukup
 * 1 query tambahan per request, di-batch untuk semua plat yang tampil di
 * halaman itu.
 *
 * Cara pakai di Controller (WAJIB dipanggil sebelum bikin Resource/response,
 * supaya cache-nya sudah terisi):
 *
 *   OwnerLookupCache::preload($daftarPlatNomor);
 *
 * Cara pakai di Resource/transform:
 *
 *   'owner' => OwnerLookupCache::get($plateNumber),
 */
class OwnerLookupCache
{
    /** @var array<string, array|null> */
    protected static array $cache = [];

    public static function normalize(?string $plate): ?string
    {
        return $plate ? strtoupper(str_replace(' ', '', $plate)) : null;
    }

    /**
     * Preload owner data untuk sekumpulan plat nomor sekaligus (1 query).
     *
     * @param array<int, string|null> $plateNumbers
     */
    public static function preload(array $plateNumbers): void
    {
        $normalizedList = collect($plateNumbers)
            ->filter()
            ->map(fn ($p) => self::normalize($p))
            ->unique()
            ->reject(fn ($p) => array_key_exists($p, self::$cache))
            ->values();

        if ($normalizedList->isEmpty()) {
            return;
        }

        // Tandai dulu semua sebagai "tidak ketemu" (null), supaya plat yang
        // memang tidak ada padanannya di db_asset_vehicles tidak query ulang.
        foreach ($normalizedList as $plate) {
            self::$cache[$plate] = null;
        }

        $rows = AssetVehicle::query()
            ->whereIn(DB::raw("REPLACE(UPPER(registration_plates), ' ', '')"), $normalizedList->all())
            ->get();

        foreach ($rows as $row) {
            $key = self::normalize($row->registration_plates);
            self::$cache[$key] = self::format($row);
        }
    }

    /**
     * Ambil owner untuk 1 plat nomor. Kalau belum ada di cache (belum
     * di-preload), otomatis query 1 baris (fallback aman untuk endpoint
     * detail /{id} yang cuma butuh 1 record).
     */
    public static function get(?string $plateNumber): ?array
    {
        $key = self::normalize($plateNumber);

        if ($key === null) {
            return null;
        }

        if (!array_key_exists($key, self::$cache)) {
            self::preload([$plateNumber]);
        }

        return self::$cache[$key] ?? null;
    }

    protected static function format(AssetVehicle $row): array
    {
        return [
            'code'          => $row->asset_owner_code,
            'company_name'  => $row->owner_company_name,
            'city'          => $row->owner_city,
            'created_at'    => optional($row->created_at)->toIso8601String(),
            'updated_at'    => optional($row->updated_at)->toIso8601String(),
            'company_group' => $row->owner_company_group,
        ];
    }

    /**
     * Kosongkan cache. Berguna untuk unit test antar-request/job.
     */
    public static function flush(): void
    {
        self::$cache = [];
    }
}
