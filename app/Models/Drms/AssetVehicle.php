<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
 *
 * CATATAN COLLATION:
 * Tabel ini pakai collation utf8mb4_0900_ai_ci, sedangkan drms_vehicles
 * pakai utf8mb4_unicode_ci. Setiap perbandingan string lintas tabel
 * WAJIB diberi COLLATE utf8mb4_unicode_ci secara eksplisit, kalau tidak
 * MySQL akan lempar error "Illegal mix of collations".
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
     * (Dipakai untuk PERBANDINGAN, bukan untuk disimpan.)
     */
    public static function normalizePlate(?string $plate): ?string
    {
        if (!$plate) {
            return null;
        }

        return strtoupper(str_replace([' ', '-', '.'], '', $plate));
    }

    /**
     * Format plat yang RAPI untuk DISIMPAN ke drms_vehicles.
     * Strip/titik diubah jadi spasi tunggal, huruf besar semua.
     * "B-2585-SNO" -> "B 2585 SNO", "B 1840 UKL." -> "B 1840 UKL"
     */
    public static function normalizePlateForStorage(?string $plate): ?string
    {
        if (!$plate) {
            return null;
        }

        $plate = strtoupper(trim($plate));
        $plate = str_replace('-', ' ', $plate);
        $plate = rtrim($plate, '.');

        return trim(preg_replace('/\s+/', ' ', $plate));
    }

    /**
     * Scope: cari 1 baris berdasarkan plat nomor (format bebas spasi/kapital).
     */
    public function scopeForPlate($query, string $plateNumber)
    {
        return $query->whereRaw(
            "REPLACE(REPLACE(REPLACE(UPPER(registration_plates), ' ', ''), '-', ''), '.', '') COLLATE utf8mb4_unicode_ci = ?",
            [self::normalizePlate($plateNumber)]
        );
    }

    /**
     * Scope: hanya aset yang statusnya masih aktif (bukan disposed/nonaktif).
     */
    public function scopeActive($query)
    {
        return $query->where('asset_status', 'active');
    }

    /**
     * Scope: exclude aset yang platnya SUDAH ada di drms_vehicles (anti-duplikat).
     * COLLATE dipaksa sama karena db_asset_vehicles (utf8mb4_0900_ai_ci) dan
     * drms_vehicles (utf8mb4_unicode_ci) beda collation.
     */
    public function scopeNotYetImported($query)
    {
        return $query->whereNotIn(
            DB::raw("REPLACE(REPLACE(REPLACE(UPPER(registration_plates), ' ', ''), '-', ''), '.', '') COLLATE utf8mb4_unicode_ci"),
            function ($sub) {
                $sub->select(DB::raw("REPLACE(REPLACE(REPLACE(UPPER(plate_number), ' ', ''), '-', ''), '.', '') COLLATE utf8mb4_unicode_ci"))
                    ->from('drms_vehicles');
            }
        );
    }

    /**
     * "Tipe" gabungan brand + model, fallback ke description kalau brand/model kosong.
     * Contoh: "Toyota INNOVA ZENIX 2.0 G A/T HYBRID"
     */
    public function getDisplayTypeAttribute(): string
    {
        $combined = trim(($this->vehicle_brand ?? '') . ' ' . ($this->vehicle_model ?? ''));

        return $combined !== '' ? $combined : (string) $this->description;
    }

    /**
     * Mapping fuel_type bebas format (Listrik/Bensin/Solar/EV/SOLAR/dst)
     * ke enum yang dipakai drms_vehicles.fuel_type.
     */
    public function getMappedFuelTypeAttribute(): ?string
    {
        $map = [
            'BENSIN'   => 'Bensin',
            'SOLAR'    => 'Solar',
            'DIESEL'   => 'Solar',
            'LISTRIK'  => 'Listrik',
            'EV'       => 'Listrik',
            'ELECTRIC' => 'Listrik',
            'HYBRID'   => 'Hybrid',
        ];

        $raw = strtoupper(trim((string) $this->fuel_type));

        if ($raw === '') {
            return null;
        }

        return $map[$raw] ?? 'Lainnya';
    }
}