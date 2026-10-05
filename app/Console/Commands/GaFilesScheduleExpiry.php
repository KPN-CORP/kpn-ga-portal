<?php

namespace App\Console\Commands;

use App\Models\GaFile;
use Illuminate\Console\Command;

/**
 * Tetapkan cloud_delete_after untuk file lama yang SUDAH terverifikasi di Local,
 * secara bertahap (per-hari) supaya tidak ada lonjakan penghapusan.
 */
class GaFilesScheduleExpiry extends Command
{
    protected $signature = 'gafiles:schedule-expiry {--prefix=} {--per-day=500} {--start-in=1}';
    protected $description = 'Jadwalkan penghapusan Cloud bertahap untuk file lama yang sudah aman di Local';

    public function handle(): int
    {
        $perDay = max(1, (int) $this->option('per-day'));
        $start = max(0, (int) $this->option('start-in'));
        $prefix = (string) $this->option('prefix');
        $i = 0;

        $q = GaFile::where('cloud_exists', 1)
            ->whereNull('cloud_delete_after')
            ->where('local_exists', 1)
            ->whereNotNull('local_verified_at')
            ->whereColumn('local_sha256', 'sha256');

        if ($prefix !== '') {
            $q->where('path', 'like', $prefix . '%');
        }

        $q->orderBy('id')->chunkById(500, function ($rows) use (&$i, $perDay, $start) {
            foreach ($rows as $f) {
                $f->update(['cloud_delete_after' => now()->addDays($start + intdiv($i, $perDay))]);
                $i++;
            }
        });

        $this->info("Dijadwalkan: {$i} file ({$perDay}/hari mulai {$start} hari lagi)");

        return self::SUCCESS;
    }
}
