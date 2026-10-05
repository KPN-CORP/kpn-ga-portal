<?php

namespace App\Services;

use App\Models\GaFile;
use App\Models\GaFileAccessRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Lapisan penyimpanan GA Portal: Cloud = cache, Local KPN = master permanen.
 * Local tidak bisa dijangkau dari luar, jadi semua transfer diinisiasi agent Local.
 */
class FileCacheService
{
    public function normalize(string $path): string
    {
        return ltrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * Daftarkan / perbarui file yang SUDAH final di disk Cloud.
     * - File baru   : version 1, local_exists=0, Cloud berlaku new_retention_days.
     * - Isi berubah : version++, Local harus sinkron ulang (local_exists=0).
     * - $legacy=true: untuk file lama (backfill), cloud_delete_after dikosongkan
     *                 sampai Local terverifikasi dan jadwal kedaluwarsa ditetapkan.
     */
    public function register(string $disk, string $path, bool $legacy = false): ?GaFile
    {
        $path = $this->normalize($path);
        $storage = Storage::disk($disk);

        if ($path === '' || ! $storage->exists($path)) {
            return null;
        }

        $full = $storage->path($path);
        $sha = hash_file('sha256', $full);
        $size = filesize($full);
        $mime = @mime_content_type($full) ?: null;
        $expiry = $legacy ? null : now()->addDays(config('gafiles.new_retention_days'));
        $uploadedAt = $legacy ? \Illuminate\Support\Carbon::createFromTimestamp(filemtime($full)) : now();

        $file = GaFile::lookup($disk, $path);

        if (! $file) {
            return GaFile::create([
                'disk'               => $disk,
                'path'               => $path,
                'path_hash'          => sha1($path),
                'size'               => $size,
                'mime'               => $mime,
                'sha256'             => $sha,
                'version'            => 1,
                'local_exists'       => false,
                'cloud_exists'       => true,
                'cloud_uploaded_at'  => $uploadedAt,
                'cloud_delete_after' => $expiry,
            ]);
        }

        if (! hash_equals($file->sha256, $sha)) {
            // Isi berubah (ditimpa / dikompres di tempat) -> versi baru, Local wajib sinkron ulang
            $file->update([
                'size'               => $size,
                'mime'               => $mime,
                'sha256'             => $sha,
                'version'            => $file->version + 1,
                'local_exists'       => false,
                'local_sha256'       => null,
                'local_verified_at'  => null,
                'local_rechecked_at' => null,
                'local_problem'      => null,
                'local_problem_at'   => null,
                'cloud_exists'       => true,
                'cloud_uploaded_at'  => now(),
                'cloud_delete_after' => $legacy ? $file->cloud_delete_after : now()->addDays(config('gafiles.new_retention_days')),
            ]);
        } elseif (! $file->cloud_exists) {
            $file->update(['cloud_exists' => true]);
        }

        return $file->fresh();
    }

    /**
     * Sajikan file. Jika tidak ada di Cloud tetapi aman di Local, buat request
     * pengambilan dan kembalikan status 202 (JSON untuk loader, halaman tunggu untuk browser).
     * File yang belum terdaftar (legacy) tetap dilayani seperti sebelumnya.
     */
    public function respond(Request $request, string $disk, string $path, array $headers = [])
    {
        $path = $this->normalize($path);
        $storage = Storage::disk($disk);
        $file = GaFile::lookup($disk, $path);

        if ($storage->exists($path)) {
            if ($file) {
                $this->touchViewed($file);
            }
            $full = $storage->path($path);
            $mime = ($file && $file->mime) ? $file->mime : (@mime_content_type($full) ?: 'application/octet-stream');

            return response()->file($full, array_merge(['Content-Type' => $mime], $headers));
        }

        if (! $file || ! $file->isLocalVerified()) {
            abort(404, 'File tidak ditemukan');
        }

        if ($file->cloud_exists) {
            $file->update(['cloud_exists' => false]); // flag basi
        }

        $req = $this->requestRestore($file, auth()->id());
        $statusUrl = route('files.request-status', $req->id);

        $accept = (string) $request->header('Accept', '');
        if (! $request->ajax() && ! $request->wantsJson() && str_contains($accept, 'text/html')) {
            return response()->view('files.restoring', ['statusUrl' => $statusUrl], 202)
                ->header('Cache-Control', 'no-store');
        }

        return response()->json([
            'status'     => 'restoring',
            'request_id' => $req->id,
            'status_url' => $statusUrl,
        ], 202)->header('Cache-Control', 'no-store')->header('Retry-After', '2');
    }

    /**
     * Maksimal satu request aktif per file; user berikutnya bergabung ke request yang sama.
     */
    public function requestRestore(GaFile $file, ?int $userId): GaFileAccessRequest
    {
        return DB::transaction(function () use ($file, $userId) {
            GaFile::whereKey($file->id)->lockForUpdate()->first();

            $active = GaFileAccessRequest::where('file_id', $file->id)
                ->whereIn('status', ['PENDING', 'PROCESSING'])
                ->orderBy('id')
                ->first();

            if ($active) {
                return $active;
            }

            return GaFileAccessRequest::create([
                'file_id'      => $file->id,
                'status'       => 'PENDING',
                'requested_by' => $userId,
            ]);
        });
    }

    /**
     * Terima file hasil upload agent (Local -> Cloud). Checksum harus sama dengan master.
     * Ditulis ke file .part lebih dulu lalu dipindah, supaya pembaca tidak pernah melihat file setengah jadi.
     */
    public function completeRestore(GaFile $file, UploadedFile $upload): array
    {
        $sha = hash_file('sha256', $upload->getRealPath());
        if (! hash_equals($file->sha256, $sha)) {
            return [false, 'Checksum file dari Local tidak cocok'];
        }

        $storage = Storage::disk($file->disk);
        $dir = dirname($file->path);
        $dir = ($dir === '.' ? '' : $dir);
        $base = basename($file->path);
        $tmp = ($dir === '' ? '' : $dir . '/') . $base . '.part';

        $storage->putFileAs($dir, $upload, $base . '.part');

        if ($storage->exists($file->path)) {
            $storage->delete($file->path);
        }
        $storage->move($tmp, $file->path);

        if (! $storage->exists($file->path) || ! hash_equals($file->sha256, hash_file('sha256', $storage->path($file->path)))) {
            return [false, 'Verifikasi file di Cloud gagal setelah ditulis'];
        }

        $file->update([
            'cloud_exists'       => true,
            'cloud_uploaded_at'  => now(),
            'cloud_delete_after' => now()->addDays(config('gafiles.restore_retention_days')),
        ]);

        return [true, 'OK'];
    }

    /**
     * Hapus salinan Cloud. Hanya boleh bila Local sudah terverifikasi untuk versi yang sama.
     * Dipakai Cron #1 dan tombol "Hapus dari Cloud" di alat Compress.
     */
    public function deleteFromCloud(GaFile $file): array
    {
        if (config('gafiles.cleanup_paused')) {
            return [false, 'Penghapusan Cloud sedang dijeda (GAFILES_CLEANUP_PAUSED) - tidak dihapus'];
        }

        if (! $file->isLocalVerified()) {
            return [false, 'Belum tersalin / terverifikasi di server Local - tidak dihapus'];
        }

        if (! $file->isLocalFresh()) {
            return [false, 'Verifikasi ulang file Local belum ada / kedaluwarsa (agent verify) - tidak dihapus'];
        }

        $storage = Storage::disk($file->disk);

        if ($storage->exists($file->path)) {
            if ((int) filesize($storage->path($file->path)) !== (int) $file->size) {
                return [false, 'Ukuran file di Cloud berbeda dari versi yang terverifikasi - tidak dihapus'];
            }
            $storage->delete($file->path);
        }

        $file->update(['cloud_exists' => false, 'cloud_delete_after' => null]);

        return [true, 'Dihapus dari Cloud (file tetap ada di Local)'];
    }

    private function touchViewed(GaFile $file): void
    {
        if (! $file->last_viewed_at || $file->last_viewed_at->lt(now()->subHour())) {
            $file->updateQuietly(['last_viewed_at' => now()]);
        }
    }
}
