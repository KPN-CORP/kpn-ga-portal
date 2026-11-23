<?php

namespace App\Console\Commands;

use App\Models\GaFile;
use App\Models\GaFileAccessRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Pemeriksaan kesehatan penyimpanan GA Portal. Dijadwalkan tiap 10 menit.
 * Masalah ditulis ke log (error) dan, bila GAFILES_ALERT_MAIL diisi, dikirim lewat email
 * (maksimal sekali per alert_cooldown_minutes untuk kumpulan masalah yang sama).
 */
class GaFilesHealth extends Command
{
    protected $signature = 'gafiles:health {--no-mail : Hanya tulis ke layar/log, jangan kirim email}';
    protected $description = 'Periksa agent Local, sinkronisasi, dan file bermasalah; kirim peringatan bila ada';

    public function handle(): int
    {
        $issues = [];
        $max = max(1, (int) config('gafiles.heartbeat_max_minutes', 15));

        // 1) Agent Local masih hidup?
        foreach (['serve' => $max, 'sync' => $max, 'verify' => $max * 3] as $mode => $limit) {
            $hb = Cache::get('gafiles.agent.heartbeat.' . $mode);
            $at = (is_array($hb) && ! empty($hb['at'])) ? Carbon::parse($hb['at']) : null;

            if (! $at) {
                $issues[] = "Agent Local (mode {$mode}) belum pernah terdengar";
            } elseif ($at->lt(now()->subMinutes($limit))) {
                $issues[] = "Agent Local (mode {$mode}) diam sejak " . $at->toDateTimeString() . " (batas {$limit} menit)";
            }
        }

        // 2) Kehilangan data nyata: Local rusak/hilang dan Cloud sudah tidak punya salinan
        $problemCount = GaFile::whereNotNull('local_problem_at')->count();
        if ($problemCount > 0) {
            $list = GaFile::whereNotNull('local_problem_at')->orderBy('local_problem_at')->limit(5)
                ->get(['path', 'local_problem'])
                ->map(fn ($f) => $f->path . ' (' . $f->local_problem . ')')
                ->implode('; ');
            $issues[] = "KRITIS: {$problemCount} file master di Local hilang/rusak dan TIDAK ada salinan di Cloud - pulihkan dari backup VM. Contoh: {$list}";
        }

        // 3) Sinkronisasi Cloud -> Local macet
        $stuck = GaFile::where('cloud_exists', 1)->where('local_exists', 0)
            ->where('updated_at', '<', now()->subDay())->count();
        if ($stuck > 0) {
            $issues[] = "{$stuck} file belum tersalin ke Local lebih dari 1 hari";
        }

        // 4) Permintaan pengambilan file macet / gagal
        $waiting = GaFileAccessRequest::whereIn('status', ['PENDING', 'PROCESSING'])
            ->where('created_at', '<', now()->subMinutes(10))->count();
        if ($waiting > 0) {
            $issues[] = "{$waiting} permintaan pengambilan file menunggu lebih dari 10 menit";
        }

        $failed = GaFileAccessRequest::where('status', 'FAILED')
            ->where('completed_at', '>=', now()->subDay())->count();
        if ($failed > 0) {
            $issues[] = "{$failed} permintaan pengambilan file GAGAL dalam 24 jam terakhir";
        }

        // 5) Jadwal hapus Cloud terlewat tetapi tidak bisa dieksekusi (pengaman bekerja, perlu dicek)
        $overdue = GaFile::where('cloud_exists', 1)->whereNotNull('cloud_delete_after')
            ->where('cloud_delete_after', '<', now()->subDays(2))->count();
        if ($overdue > 0) {
            $issues[] = "{$overdue} file Cloud lewat jadwal hapus >2 hari tetapi belum bisa dihapus (Local belum terverifikasi / verifikasi ulang kedaluwarsa / penghapusan dijeda)";
        }

        if (! $issues) {
            $this->info('OK: tidak ada masalah.');

            return self::SUCCESS;
        }

        foreach ($issues as $i) {
            $this->error($i);
        }
        Log::error('GaFiles health: ' . implode(' | ', $issues));

        $this->notify($issues);

        return self::FAILURE;
    }

    private function notify(array $issues): void
    {
        $to = array_values(array_filter(array_map('trim', explode(',', (string) config('gafiles.alert_mail')))));
        if (! $to || $this->option('no-mail')) {
            return;
        }

        // Kunci dari isi pesan tanpa angka, supaya perubahan hitungan/waktu tidak memicu email baru
        $key = 'gafiles.health.mailed.' . md5(implode('|', array_map(fn ($i) => preg_replace('/\d+/', '#', $i), $issues)));
        $cooldown = max(1, (int) config('gafiles.alert_cooldown_minutes', 360));

        if (! Cache::add($key, 1, now()->addMinutes($cooldown))) {
            return;
        }

        try {
            $body = "Peringatan penyimpanan file GA Portal:\n\n- " . implode("\n- ", $issues)
                . "\n\nWaktu: " . now()->toDateTimeString();

            Mail::raw($body, function ($m) use ($to) {
                $m->to($to)->subject('[GA Portal] Peringatan penyimpanan file');
            });
        } catch (\Throwable $e) {
            Log::error('GaFiles health: gagal mengirim email peringatan - ' . $e->getMessage());
        }
    }
}
