<?php

namespace App\Console\Commands;

use App\Models\GaFile;
use App\Services\FileCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Daftarkan file lama ke ga_files supaya agent Local bisa menyalinnya.
 * File lama didaftarkan TANPA jadwal hapus (cloud_delete_after = null).
 * Jadwal kedaluwarsa ditetapkan terpisah (gafiles:schedule-expiry) setelah Local terverifikasi.
 */
class GaFilesBackfill extends Command
{
    protected $signature = 'gafiles:backfill-mailing {--disk=public} {--prefix=mailing-foto} {--only-new : Lewati file yang sudah terdaftar (hemat untuk jadwal harian)}';
    protected $description = 'Daftarkan file foto Mailing Room yang sudah ada ke ga_files';

    public function handle(FileCacheService $svc): int
    {
        $disk = $this->option('disk');
        $prefix = trim($this->option('prefix'), '/');
        $storage = Storage::disk($disk);

        $paths = [];
        foreach ($storage->allFiles($prefix) as $p) {
            $paths[$svc->normalize($p)] = true;
        }

        // Path yang direferensikan DB (termasuk fallback temp/...)
        $dbPaths = DB::table('tb_mailing')->whereNotNull('mailing_foto')->where('mailing_foto', '!=', '')
            ->distinct()->pluck('mailing_foto');

        // Referensi DB yang tidak ada di Cloud DAN tidak terdaftar = tidak pernah tersimpan / sudah hilang.
        // (File yang memang sudah dihapus dari Cloud tetapi aman di Local terdaftar di ga_files, jadi tidak dihitung.)
        $missing = 0;
        $missingList = [];
        foreach ($dbPaths as $p) {
            $p = $svc->normalize($p);
            if ($storage->exists($p)) {
                $paths[$p] = true;
            } elseif (! GaFile::lookup($disk, $p)) {
                $missing++;
                if (count($missingList) < 10) {
                    $missingList[] = $p;
                }
            }
        }

        $onlyNew = (bool) $this->option('only-new');
        $count = 0;
        $skipped = 0;
        foreach (array_keys($paths) as $p) {
            if (str_ends_with($p, '.tmp') || str_ends_with($p, '.part')) {
                continue;
            }
            if ($onlyNew && GaFile::lookup($disk, $p)) {
                $skipped++;
                continue;
            }
            if ($svc->register($disk, $p, true)) {
                $count++;
            }
        }

        $this->info("Terdaftar/diperbarui: {$count} file, dilewati (sudah terdaftar): {$skipped}.");

        if ($missing > 0) {
            $this->warn("Referensi tb_mailing tanpa file di Cloud dan belum terdaftar (potensi hilang): {$missing}");
            foreach ($missingList as $p) {
                $this->line('  - ' . $p);
            }
            Log::warning("GaFiles backfill: {$missing} referensi mailing_foto tanpa file & belum terdaftar. Contoh: " . implode(', ', $missingList));
        }

        return self::SUCCESS;
    }
}
