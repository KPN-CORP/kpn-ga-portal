<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CompressFotomailingController extends Controller
{
    private function isAdmin()
    {
        $username = Auth::user()->username;
        $access = DB::table('tb_access_menu')->where('username', $username)->first();
        return $access && isset($access->mailing_proses) && $access->mailing_proses == 1;
    }

    public function index()
    {
        if (!$this->isAdmin()) {
            abort(403, 'Hanya admin yang dapat mengakses halaman ini.');
        }

        $directory = storage_path('app/public/mailing-foto');
        $files = [];

        if (is_dir($directory)) {
            $allFiles = glob($directory . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE);
            foreach ($allFiles as $file) {
                $size = filesize($file);
                $files[] = [
                    'name' => basename($file),
                    'size_mb' => round($size / 1024 / 1024, 2),
                    'need_compress' => $size > (1.5 * 1024 * 1024)
                ];
            }
        }

        $totalFiles = count($files);
        $needCompress = count(array_filter($files, fn($f) => $f['need_compress']));

        return view('mailing.kompres', compact('totalFiles', 'needCompress', 'files'));
    }

    public function proses(Request $request)
    {
        if (!$this->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'string'
        ]);

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $maxSizeBytes = 1.5 * 1024 * 1024;
        $results = [];

        // PERBAIKAN: gunakan $request->input('files') BUKAN $request->files
        foreach ($request->input('files') as $filename) {
            $filePath = storage_path('app/public/mailing-foto/' . $filename);

            if (!file_exists($filePath)) {
                $results[] = ['name' => $filename, 'status' => 'skip', 'message' => 'File tidak ditemukan'];
                continue;
            }

            $currentSize = filesize($filePath);
            if ($currentSize <= $maxSizeBytes) {
                $results[] = ['name' => $filename, 'status' => 'skip', 'message' => 'Sudah ≤ 1.5 MB'];
                continue;
            }

            $success = $this->compressImageKeepFormat($filePath, $maxSizeBytes);

            if ($success) {
                $newSize = filesize($filePath);
                $results[] = [
                    'name' => $filename,
                    'status' => 'success',
                    'old_mb' => round($currentSize / 1024 / 1024, 2),
                    'new_mb' => round($newSize / 1024 / 1024, 2)
                ];
            } else {
                $results[] = ['name' => $filename, 'status' => 'failed', 'message' => 'Gagal kompres'];
            }
        }

        return response()->json(['results' => $results]);
    }

    private function compressImageKeepFormat($filePath, $maxSizeBytes)
    {
        try {
            $info = getimagesize($filePath);
            if (!$info) return false;

            $mime = $info['mime'];
            $tempPath = $filePath . '.tmp';

            switch ($mime) {
                case 'image/jpeg':
                    $src = imagecreatefromjpeg($filePath);
                    if (!$src) return false;
                    $quality = 90;
                    $minQuality = 20;
                    $success = false;
                    while ($quality >= $minQuality) {
                        imagejpeg($src, $tempPath, $quality);
                        clearstatcache();
                        if (filesize($tempPath) <= $maxSizeBytes) {
                            $success = true;
                            break;
                        }
                        $quality -= 5;
                    }
                    imagedestroy($src);
                    if ($success && file_exists($tempPath)) {
                        rename($tempPath, $filePath);
                        return true;
                    }
                    if (file_exists($tempPath)) unlink($tempPath);
                    return false;

                case 'image/webp':
                    $src = imagecreatefromwebp($filePath);
                    if (!$src) return false;
                    $quality = 90;
                    $minQuality = 20;
                    $success = false;
                    while ($quality >= $minQuality) {
                        imagewebp($src, $tempPath, $quality);
                        clearstatcache();
                        if (filesize($tempPath) <= $maxSizeBytes) {
                            $success = true;
                            break;
                        }
                        $quality -= 5;
                    }
                    imagedestroy($src);
                    if ($success && file_exists($tempPath)) {
                        rename($tempPath, $filePath);
                        return true;
                    }
                    if (file_exists($tempPath)) unlink($tempPath);
                    return false;

                case 'image/png':
                    $src = imagecreatefrompng($filePath);
                    if (!$src) return false;

                    $originalWidth = imagesx($src);
                    $originalHeight = imagesy($src);

                    // Try lossless compression first
                    imagepng($src, $tempPath, 9);
                    clearstatcache();
                    if (filesize($tempPath) <= $maxSizeBytes) {
                        rename($tempPath, $filePath);
                        imagedestroy($src);
                        return true;
                    }

                    // Progressive resize
                    $scale = 0.9;
                    $minScale = 0.2;
                    $success = false;

                    while ($scale >= $minScale) {
                        $newWidth = (int)($originalWidth * $scale);
                        $newHeight = (int)($originalHeight * $scale);

                        $resized = imagecreatetruecolor($newWidth, $newHeight);
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
                        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);

                        imagecopyresampled($resized, $src, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
                        imagepng($resized, $tempPath, 9);
                        imagedestroy($resized);
                        clearstatcache();

                        if (filesize($tempPath) <= $maxSizeBytes) {
                            $success = true;
                            break;
                        }
                        $scale -= 0.05;
                    }

                    imagedestroy($src);

                    if ($success && file_exists($tempPath)) {
                        rename($tempPath, $filePath);
                        return true;
                    }

                    if (file_exists($tempPath)) unlink($tempPath);
                    return false;

                default:
                    return false;
            }
        } catch (\Exception $e) {
            \Log::error('Compress error: ' . $e->getMessage());
            return false;
        }
    }
}