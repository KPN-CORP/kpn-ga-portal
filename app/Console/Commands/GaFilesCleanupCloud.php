<?php

namespace App\Console\Commands;

use App\Models\GaFile;
use App\Services\FileCacheService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * CRON #1 - hapus salinan Cloud yang sudah lewat cloud_delete_after,
 * hanya jika Local sudah terverifikasi untuk versi yang sama.
 */
class GaFilesCleanupCloud extends Command
{
    protected $signature = 'gafiles:cleanup-cloud {--dry-run}';
    protected $description = 'Hapus file Cloud yang kedaluwarsa (hanya jika Local terverifikasi)';

    public function handle(FileCacheService $svc): int
    {
        $dry = (bool) $this->option('dry-run');
        $deleted = 0;
        $skipped = 0;

        GaFile::where('cloud_exists', 1)
            ->whereNotNull('cloud_delete_after')
            ->where('cloud_delete_after', '<=', now())
            ->chunkById(200, function ($rows) use ($svc, $dry, &$deleted, &$skipped) {
                foreach ($rows as $f) {
                    if ($dry) {
                        (! config('gafiles.cleanup_paused') && $f->isLocalFresh()) ? $deleted++ : $skipped++;
                        continue;
                    }

                    [$ok, $msg] = $svc->deleteFromCloud($f);
                    if ($ok) {
                        $deleted++;
                    } else {
                        $skipped++;
                        // Sudah lewat jadwal tapi Local belum aman -> perlu perhatian admin
                        if ($f->cloud_delete_after->lt(now()->subDays(2))) {
                            Log::warning("GaFiles cleanup dilewati (#{$f->id} {$f->path}): {$msg}");
                        }
                    }
                }
            });

        $this->info(($dry ? '[dry-run] ' : '') . "dihapus: {$deleted}, dilewati (Local belum aman): {$skipped}");

        return self::SUCCESS;
    }
}
