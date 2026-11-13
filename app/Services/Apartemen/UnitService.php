<?php

namespace App\Services\Apartemen;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Satu sumber kebenaran untuk ketersediaan & status unit apartemen.
 *
 * Aturan:
 *  - Unit TERISI  = ada penempatan AKTIF yang sudah mulai (tanggal_mulai <= hari ini) dan masih punya penghuni AKTIF.
 *  - Unit READY   = selain itu. Booking yang belum mulai TIDAK membuat unit TERISI.
 *  - MAINTENANCE  = diatur manual, tidak pernah diubah otomatis.
 *  - Bentrok jadwal dicek dari TANGGAL penempatan, bukan dari kolom status unit.
 */
class UnitService
{
    private const OCCUPIED_SQL = "EXISTS (
        SELECT 1 FROM tb_apartemen_assign a
        WHERE a.unit_id = u.id
          AND a.status = 'AKTIF'
          AND a.deleted_at IS NULL
          AND DATE(a.tanggal_mulai) <= ?
          AND EXISTS (
              SELECT 1 FROM tb_apartemen_penghuni p
              WHERE p.assign_id = a.id AND p.status = 'AKTIF' AND p.deleted_at IS NULL
          )
    )";

    /** Apakah unit sedang benar-benar dihuni hari ini? */
    public static function terisiSekarang(int $unitId): bool
    {
        return DB::table('tb_apartemen_assign as a')
            ->where('a.unit_id', $unitId)
            ->where('a.status', 'AKTIF')
            ->whereNull('a.deleted_at')
            ->whereDate('a.tanggal_mulai', '<=', now()->toDateString())
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('tb_apartemen_penghuni as p')
                    ->whereColumn('p.assign_id', 'a.id')
                    ->where('p.status', 'AKTIF')
                    ->whereNull('p.deleted_at');
            })
            ->exists();
    }

    /** Samakan status SATU unit dengan kenyataan (MAINTENANCE dibiarkan). */
    public static function sinkron(?int $unitId): void
    {
        if (!$unitId) {
            return;
        }

        $unit = DB::table('tb_apartemen_unit')
            ->where('id', $unitId)
            ->whereNull('deleted_at')
            ->first(['id', 'status']);

        if (!$unit || $unit->status === 'MAINTENANCE') {
            return;
        }

        $baru = self::terisiSekarang($unitId) ? 'TERISI' : 'READY';
        if ($baru !== $unit->status) {
            DB::table('tb_apartemen_unit')
                ->where('id', $unitId)
                ->update(['status' => $baru, 'updated_at' => now()]);
        }
    }

    /**
     * Samakan status SEMUA unit. Secara default hanya jalan sekali per menit
     * (aman dipanggil dari dashboard / daftar unit). $paksa = true untuk perintah artisan / skrip.
     *
     * @return int jumlah unit yang berubah
     */
    public static function sinkronSemua(bool $paksa = false): int
    {
        if (!$paksa && !Cache::add('apartemen:sinkron-unit', 1, 60)) {
            return 0;
        }

        $today = now()->toDateString();
        $now   = now();
        $occ   = self::OCCUPIED_SQL;

        $jadiTerisi = DB::update(
            "UPDATE tb_apartemen_unit u SET u.status = 'TERISI', u.updated_at = ?
             WHERE u.deleted_at IS NULL AND u.status = 'READY' AND {$occ}",
            [$now, $today]
        );

        $jadiReady = DB::update(
            "UPDATE tb_apartemen_unit u SET u.status = 'READY', u.updated_at = ?
             WHERE u.deleted_at IS NULL AND u.status = 'TERISI' AND NOT {$occ}",
            [$now, $today]
        );

        return $jadiTerisi + $jadiReady;
    }

    /**
     * Apakah rentang [mulai, selesai] bentrok dengan penempatan AKTIF lain di unit ini?
     * Bersinggungan di satu hari (selesai = mulai berikutnya) diperbolehkan, seperti aturan sebelumnya
     * (check-out pagi, check-in siang).
     */
    public static function bentrok(int $unitId, $mulai, $selesai, ?int $kecualiAssignId = null): bool
    {
        $mulai   = Carbon::parse($mulai)->toDateString();
        $selesai = Carbon::parse($selesai)->toDateString();

        $q = DB::table('tb_apartemen_assign')
            ->where('unit_id', $unitId)
            ->where('status', 'AKTIF')
            ->whereNull('deleted_at')
            ->whereDate('tanggal_mulai', '<', $selesai)
            ->whereDate('tanggal_selesai', '>', $mulai);

        if ($kecualiAssignId) {
            $q->where('id', '!=', $kecualiAssignId);
        }

        return $q->exists();
    }

    /**
     * Apakah karyawan ini sudah punya penempatan AKTIF yang jadwalnya beririsan?
     * ID kosong / "-" tidak bisa dicek (dianggap tidak bentrok).
     */
    public static function penghuniBentrok(?string $idKaryawan, $mulai, $selesai, ?int $kecualiAssignId = null): bool
    {
        $idKaryawan = trim((string) $idKaryawan);
        if ($idKaryawan === '' || $idKaryawan === '-') {
            return false;
        }

        $mulai   = Carbon::parse($mulai)->toDateString();
        $selesai = Carbon::parse($selesai)->toDateString();

        $q = DB::table('tb_apartemen_penghuni as p')
            ->join('tb_apartemen_assign as a', 'a.id', '=', 'p.assign_id')
            ->where('p.id_karyawan', $idKaryawan)
            ->where('p.status', 'AKTIF')
            ->whereNull('p.deleted_at')
            ->where('a.status', 'AKTIF')
            ->whereNull('a.deleted_at')
            ->whereDate('a.tanggal_mulai', '<', $selesai)
            ->whereDate('a.tanggal_selesai', '>', $mulai);

        if ($kecualiAssignId) {
            $q->where('a.id', '!=', $kecualiAssignId);
        }

        return $q->exists();
    }
}
