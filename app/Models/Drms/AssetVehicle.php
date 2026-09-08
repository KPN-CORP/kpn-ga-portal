<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;

/**
 * Model READ-ONLY untuk tabel db_asset_vehicles.
 *
 * Tabel ini adalah data ASET KENDARAAN milik AMS yang disinkronkan (mirror)
 * ke database GA Portal/DRMS — lihat db_asset_vehicles.sql yang sudah dikirim.
 * DRMS TIDAK menulis ke tabel ini; pengisian/refresh datanya dilakukan oleh
 * job sync terpisah yang menarik data dari AMS (lihat
 * app/Console/Commands/SyncAmsAssetVehicles.php).
 *
 * Kolom kunci untuk join ke data DRMS: registration_plates
 * (di GA Portal/DRMS disebut plate_number).
 */
class AssetVehicle extends Model
{
    protected $table = 'db_asset_vehicles';

    // created_at/updated_at di tabel ini adalah timestamp dari sisi AMS
    // (diisi oleh job sync), bukan dikelola Eloquent saat runtime DRMS.
    public $timestamps = false;

    protected $casts = [
        'capitalization_date' => 'date',
        'vrc_due_date'        => 'date',
        'created_at'          => 'datetime',
        'updated_at'          => 'datetime',
        'synced_at'           => 'datetime',
        'acquisition_value'   => 'decimal:2',
    ];

    /**
     * Normalisasi plat nomor supaya "B 1929 SDW" == "B1929SDW" == "b1929sdw".
     */
    public static function normalizePlate(?string $plate): ?string
    {
        if (!$plate) {
            return null;
        }

        return strtoupper(str_replace(' ', '', $plate));
    }

    /**
     * Scope: cari 1 baris berdasarkan plat nomor (format bebas spasi/kapital).
     */
    public function scopeForPlate($query, string $plateNumber)
    {
        return $query->whereRaw(
            "REPLACE(UPPER(registration_plates), ' ', '') = ?",
            [self::normalizePlate($plateNumber)]
        );
    }
}
