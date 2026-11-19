<?php

namespace App\Services;

use App\Models\GaFile;
use Illuminate\Support\Facades\Storage;

/**
 * Menemukan file di storage Cloud yang belum tercatat di ga_files (atau isinya berubah)
 * supaya agent Local ikut mencadangkannya.
 *
 * HANYA mendaftarkan - tidak pernah menghapus apa pun. File didaftarkan sebagai "legacy"
 * (cloud_delete_after = null), jadi tidak ada jadwal hapus otomatis.
 */
class FileDiscoveryService
{
    /** Disk yang disisir: 'public' = storage/app/public, 'local' = storage/app/private. */
    public const DISKS = ['public', 'local'];

    public function __construct(private FileCacheService $svc)
    {
    }

    /**
     * @param  int  $limit          maks. file yang didaftarkan/diperbarui per panggilan (tiap file di-hash)
     * @param  int  $budgetSeconds  batas waktu per panggilan
     * @return array{registered:int,updated:int,known:int,skipped:int,invalid:int,more:bool}
     */
    public function run(int $limit = 200, int $budgetSeconds = 25, bool $dry = false): array
    {
        $deadline = microtime(true) + $budgetSeconds;
        $stat = ['registered' => 0, 'updated' => 0, 'known' => 0, 'skipped' => 0, 'invalid' => 0, 'more' => false];
        $work = 0;

        foreach (self::DISKS as $disk) {
            $storage = Storage::disk($disk);
            $known = GaFile::where('disk', $disk)->toBase()
                ->get(['path_hash', 'size', 'cloud_uploaded_at'])->keyBy('path_hash');

            foreach ($storage->allFiles() as $raw) {
                $p = $this->svc->normalize($raw);

                if ($this->isExcluded($p)) {
                    $stat['skipped']++;
                    continue;
                }
                // agent menolak path dengan ".." (anti path-traversal), jadi jangan didaftarkan
                if (str_contains($p, '..') || str_contains($p, "\0")) {
                    $stat['invalid']++;
                    continue;
                }

                $row = $known->get(sha1($p));

                if ($row) {
                    $full = $storage->path($p);
                    $size = @filesize($full);
                    $mtime = @filemtime($full);
                    $uploaded = $row->cloud_uploaded_at ? (int) strtotime($row->cloud_uploaded_at) : 0;
                    $changed = $size !== false
                        && ((int) $size !== (int) $row->size || ($mtime !== false && $mtime > $uploaded + 5));

                    if (! $changed) {
                        $stat['known']++;
                        continue;
                    }
                }

                if ($work >= $limit || microtime(true) >= $deadline) {
                    $stat['more'] = true;
                    break 2;
                }
                $work++;

                if (! $dry) {
                    $this->svc->register($disk, $p, true); // isi berubah -> versi naik -> Local sinkron ulang
                }
                $row ? $stat['updated']++ : $stat['registered']++;
            }
        }

        return $stat;
    }

    private function isExcluded(string $p): bool
    {
        $base = basename($p);

        if (in_array($base, ['.gitignore', '.DS_Store', 'Thumbs.db'], true) || str_starts_with($base, '.ga-agent-check')) {
            return true;
        }
        if (preg_match('/\.(part|tmp)$/i', $base)) {
            return true;
        }
        foreach ((array) config('gafiles.discover_exclude', []) as $prefix) {
            $prefix = ltrim((string) $prefix, '/');
            // cocok di awal path ATAU di folder mana pun (mis. 'temp/' juga mengecualikan 'messenger/temp/x.jpg')
            if ($prefix !== '' && (str_starts_with($p, $prefix) || str_contains($p, '/' . $prefix))) {
                return true;
            }
        }

        return false;
    }
}
