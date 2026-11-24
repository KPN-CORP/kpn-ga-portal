<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    /** Ekstensi gambar yang boleh dilihat di viewer / diputar. */
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function show($path)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Anda harus login.');
        }

        $this->authorizeImageAccess($user, $path);

        $fullPath = $this->resolveFullPath($path);

        // Tanpa cache lama (max-age) supaya foto yang baru diputar langsung tampil benar.
        // Browser tetap hemat bandwidth lewat ETag: file tidak berubah -> 304.
        $etag = '"' . md5($fullPath . filemtime($fullPath) . filesize($fullPath)) . '"';
        $sent = trim((string) request()->header('If-None-Match'));
        if ($sent !== '' && preg_replace('#^W/#', '', $sent) === $etag) {
            return response('', 304, ['ETag' => $etag, 'Cache-Control' => 'private, no-cache']);
        }

        $mime = mime_content_type($fullPath) ?: 'image/jpeg';
        return response()->file($fullPath, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, no-cache',
            'ETag' => $etag,
        ]);
    }

    /**
     * Halaman viewer foto: putar kiri/kanan, zoom, dan (khusus admin) simpan rotasi permanen.
     * File non-gambar (mis. PDF) langsung diteruskan ke tampilan biasa.
     */
    public function viewer($path)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Anda harus login.');
        }

        $this->authorizeImageAccess($user, $path);
        $fullPath = $this->resolveFullPath($path);
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

        if (!in_array($ext, self::IMAGE_EXT, true)) {
            return redirect()->route('drms.private.image', $path);
        }

        $canRotate = $this->canRotate($user) && extension_loaded('gd');

        return view('drms.image_viewer', [
            'src'       => route('drms.private.image', $path) . '?v=' . filemtime($fullPath),
            'filename'  => basename($fullPath),
            'path'      => $path,
            'rotateUrl' => $canRotate ? route('drms.private.image.rotate') : null,
        ]);
    }

    /**
     * Simpan rotasi gambar secara permanen (menimpa file aktif).
     * Body: path, degrees = 90 | 180 | 270 (searah jarum jam).
     * Hanya admin / superadmin DRMS.
     */
    public function rotate(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Anda harus login.');
        }
        if (!$this->canRotate($user)) {
            return response()->json(['message' => 'Anda tidak berwenang mengubah foto ini.'], 403);
        }

        $path = (string) $request->input('path');
        $degrees = (int) $request->input('degrees');
        if (!in_array($degrees, [90, 180, 270], true)) {
            return response()->json(['message' => 'Sudut rotasi tidak valid.'], 422);
        }

        // Aturan akses yang sama dengan melihat foto (BU yang sama, dst.)
        $this->authorizeImageAccess($user, $path);
        $fullPath = $this->resolveFullPath($path);

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (!in_array($ext, self::IMAGE_EXT, true) || !extension_loaded('gd')) {
            return response()->json(['message' => 'Jenis file ini tidak bisa diputar.'], 422);
        }

        $loaders = [
            'jpg' => 'imagecreatefromjpeg', 'jpeg' => 'imagecreatefromjpeg',
            'png' => 'imagecreatefrompng', 'gif' => 'imagecreatefromgif', 'webp' => 'imagecreatefromwebp',
        ];
        $img = @$loaders[$ext]($fullPath);
        if (!$img) {
            return response()->json(['message' => 'Gagal membaca gambar.'], 500);
        }

        // Browser menampilkan JPEG sesuai EXIF, sedangkan GD mengabaikannya. Samakan dulu.
        if (in_array($ext, ['jpg', 'jpeg'], true) && function_exists('exif_read_data')) {
            $exif = @exif_read_data($fullPath);
            $fix = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
            if ($fix) {
                $img = imagerotate($img, $fix, 0);
            }
        }

        // imagerotate berputar berlawanan arah jarum jam -> konversi ke searah jarum jam
        $angle = 360 - $degrees;
        if (in_array($ext, ['png', 'webp', 'gif'], true)) {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
            $rotated = imagerotate($img, $angle, $transparent);
            imagealphablending($rotated, false);
            imagesavealpha($rotated, true);
        } else {
            $rotated = imagerotate($img, $angle, 0);
        }
        imagedestroy($img);

        // Tulis ke file sementara dulu, baru ganti file asli (tidak ada file setengah jadi kalau gagal).
        $tmp = $fullPath . '.rot' . uniqid();
        $saved = match ($ext) {
            'jpg', 'jpeg' => imagejpeg($rotated, $tmp, 90),
            'png'         => imagepng($rotated, $tmp),
            'gif'         => imagegif($rotated, $tmp),
            'webp'        => imagewebp($rotated, $tmp, 90),
        };
        imagedestroy($rotated);

        if (!$saved || !file_exists($tmp)) {
            @unlink($tmp);
            return response()->json(['message' => 'Gagal menyimpan gambar.'], 500);
        }
        @chmod($tmp, fileperms($fullPath) & 0777);
        if (!@rename($tmp, $fullPath)) {
            @unlink($tmp);
            return response()->json(['message' => 'Gagal mengganti file.'], 500);
        }
        clearstatcache(true, $fullPath);

        Log::info("DRMS foto diputar {$degrees}° oleh user {$user->id}: {$path}");

        return response()->json(['ok' => true]);
    }

    /** Hanya admin DRMS dan superadmin yang boleh menyimpan rotasi. */
    private function canRotate($user): bool
    {
        return $user->isDrmsSuperAdmin() || $user->isDrmsAdmin();
    }

    /**
     * Path absolut file di storage/app/private, dengan pengaman path traversal.
     * Abort 404 kalau path mencurigakan atau file tidak ada.
     */
    private function resolveFullPath(string $path): string
    {
        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0") || str_starts_with($path, '/')) {
            abort(404);
        }

        $base = realpath(storage_path('app/private'));
        $full = $base ? realpath($base . DIRECTORY_SEPARATOR . $path) : false;

        if (!$base || !$full || strpos($full, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($full)) {
            abort(404);
        }

        return $full;
    }

    private function authorizeImageAccess($user, $path)
    {
        if ($user->isDrmsSuperAdmin()) {
            return;
        }

        if ($user->isDrmsAdmin()) {
            $buId = $user->drmsProfile->business_unit_id;
            $this->checkImageInSameBu($path, $buId);
            return;
        }

        if ($user->driver) {
            $driverId = $user->driver->id;
            $this->checkImageBelongsToDriver($path, $driverId);
            return;
        }

        abort(403);
    }

    private function checkImageInSameBu($path, $buId)
    {
        // Pakai get() bukan first(): satu path foto sekarang bisa direferensikan oleh
        // lebih dari satu TripLog (photo_after trip lama dipinjam jadi photo_before
        // trip baru untuk driver berikutnya), jadi semua kemungkinan log harus dicek.
        $logs = \App\Models\Drms\TripLog::where('photo_before', $path)
            ->orWhere('photo_after', $path)
            ->orWhere('photo_fuel_receipt', $path)
            ->get();
        foreach ($logs as $log) {
            $requestBu = $log->request->current_business_unit_id ?? $log->request->requester->drmsProfile->business_unit_id ?? null;
            if ($requestBu == $buId) return;
        }

        // Struk + foto dashboard speedometer (sebelum & setelah pengisian) pada log BBM
        $fuelLog = \App\Models\Drms\FuelLog::where(function ($q) use ($path) {
            $q->where('receipt_file', $path)
              ->orWhere('photo_dashboard_before', $path)
              ->orWhere('photo_dashboard_after', $path);
        })->first();
        if ($fuelLog && $fuelLog->vehicle && $fuelLog->vehicle->business_unit_id == $buId) return;

        $service = \App\Models\Drms\ServiceSchedule::where('invoice_file', $path)->first();
        if ($service && $service->vehicle && $service->vehicle->business_unit_id == $buId) return;

        $repair = \App\Models\Drms\Repair::where('invoice_file', $path)->first();
        if ($repair && $repair->vehicle && $repair->vehicle->business_unit_id == $buId) return;

        abort(403);
    }

    private function checkImageBelongsToDriver($path, $driverId)
    {
        // Sama seperti di atas: cek semua log yang mereferensikan path ini, karena
        // foto "sesudah" trip sebelumnya bisa dipakai/dipinjam sebagai foto "sebelum"
        // oleh driver lain di trip berikutnya untuk kendaraan yang sama.
        $logs = \App\Models\Drms\TripLog::where('photo_before', $path)
            ->orWhere('photo_after', $path)
            ->orWhere('photo_fuel_receipt', $path)
            ->get();
        foreach ($logs as $log) {
            if ($log->request && $log->request->driver_id == $driverId) return;
        }

        $fuelLog = \App\Models\Drms\FuelLog::where(function ($q) use ($path) {
            $q->where('receipt_file', $path)
              ->orWhere('photo_dashboard_before', $path)
              ->orWhere('photo_dashboard_after', $path);
        })->first();
        if ($fuelLog && $fuelLog->driver_id == $driverId) return;

        abort(403);
    }
}