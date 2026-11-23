<?php

namespace App\Console\Commands;

use App\Services\FileDiscoveryService;
use Illuminate\Console\Command;

/**
 * Daftarkan SEMUA file di storage/app (public + private) ke ga_files supaya dicadangkan agent Local.
 * Aman dijalankan berulang: file yang sudah terdaftar dan tidak berubah dilewati.
 */
class GaFilesBackfillAll extends Command
{
    protected $signature = 'gafiles:backfill-all
        {--limit=2000 : Maks. file didaftarkan/diperbarui per eksekusi (tiap file di-hash)}
        {--budget=600 : Batas waktu dalam detik}
        {--dry-run : Hanya hitung, tidak mendaftarkan}';

    protected $description = 'Daftarkan semua file di storage/app (public + private) ke ga_files agar dicadangkan ke Local';

    public function handle(FileDiscoveryService $svc): int
    {
        $dry = (bool) $this->option('dry-run');
        $r = $svc->run(max(1, (int) $this->option('limit')), max(5, (int) $this->option('budget')), $dry);

        $this->info(($dry ? '[dry-run] ' : '')
            . "baru: {$r['registered']}, berubah: {$r['updated']}, sudah terdaftar: {$r['known']}, "
            . "dilewati (temp/.part/dll): {$r['skipped']}, path tidak valid: {$r['invalid']}");

        if ($r['more']) {
            $this->warn('Masih ada file lain. Jalankan perintah ini lagi sampai tidak ada peringatan ini.');
        }

        return self::SUCCESS;
    }
}
