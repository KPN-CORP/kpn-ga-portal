<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CompressFotomailingController extends Controller
{
    private function isAdmin()
    {
        $username = Auth::user()->username;
        $access = DB::table('tb_access_menu')->where('username', $username)->first();
        return $access && isset($access->mailing_proses) && $access->mailing_proses == 1;
    }

    // ================= BROWSE FOLDER =================
    public function browse(Request $request)
    {
        if (!$this->isAdmin()) abort(403);

        $currentPath = $request->get('path', '');
        // Security: cegah path traversal
        if (strpos($currentPath, '..') !== false) {
            $currentPath = '';
        }

        $baseDir = storage_path('app');
        $fullPath = $baseDir . ($currentPath ? '/' . $currentPath : '');

        if (!is_dir($fullPath)) {
            return redirect()->route('mailing.kompres.browse');
        }

        $items = scandir($fullPath);
        $directories = [];
        $images = [];
        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'JPG', 'JPEG', 'PNG', 'WEBP'];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $itemPath = $fullPath . '/' . $item;
            if (is_dir($itemPath)) {
                $directories[] = $item;
            } elseif (is_file($itemPath) && in_array(pathinfo($item, PATHINFO_EXTENSION), $extensions)) {
                $size = filesize($itemPath);
                // Buat relative path untuk digunakan di route gambar
                $relativePath = $currentPath ? $currentPath . '/' . $item : $item;
                $images[] = [
                    'name' => $item,
                    'size_mb' => round($size / 1024 / 1024, 2),
                    'need_compress' => $size > (1.5 * 1024 * 1024),
                    'url' => url('/mailing/kompres/image?path=' . $relativePath), // <-- URL preview
                ];
            }
        }

        sort($directories);
        sort($images);

        return view('mailing.browse', compact('currentPath', 'directories', 'images'));
    }

    /**
     * Menampilkan file gambar (untuk preview)
     */
    public function showImage(Request $request)
    {
        if (!$this->isAdmin()) abort(403);

        $path = $request->get('path');
        // Security: cegah path traversal
        if (strpos($path, '..') !== false) {
            abort(404);
        }

        $fullPath = storage_path('app/' . $path);
        if (!file_exists($fullPath) || !is_file($fullPath)) {
            abort(404);
        }

        // Cek ekstensi gambar
        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
            abort(404);
        }

        // Tentukan MIME type
        $mime = mime_content_type($fullPath);
        if (!$mime) {
            $mime = 'image/' . $ext;
        }

        return response()->file($fullPath, ['Content-Type' => $mime]);
    }

    // ================= HALAMAN KOMPRES (dengan folder pilihan) =================
    public function index(Request $request)
    {
        if (!$this->isAdmin()) abort(403);

        $selectedFolder = $request->get('folder', 'public/mailing-foto');
        if (strpos($selectedFolder, '..') !== false) {
            $selectedFolder = 'public/mailing-foto';
        }

        $baseDir = storage_path('app/' . $selectedFolder);
        if (!is_dir($baseDir)) {
            abort(404, 'Folder tidak ditemukan');
        }

        $files = $this->getAllImagesRecursive($selectedFolder);
        $totalFiles = count($files);
        $needCompress = count(array_filter($files, fn($f) => $f['need_compress']));

        $availableFolders = [
            'public/mailing-foto' => 'Mailing Foto (public/mailing-foto)',
            'public' => 'Seluruh folder public (rekursif)',
            'private' => 'Folder private (rekursif)',
        ];
        $folders = [];
        foreach ($availableFolders as $path => $label) {
            if (is_dir(storage_path('app/' . $path))) {
                $folders[$path] = $label;
            }
        }

        return view('mailing.kompres', compact('selectedFolder', 'files', 'totalFiles', 'needCompress', 'folders'));
    }

    /**
     * Ambil semua file gambar dari suatu folder (rekursif untuk public/private)
     */
    private function getAllImagesRecursive($folderPath)
    {
        $basePath = storage_path('app/' . $folderPath);
        if (!is_dir($basePath)) return [];

        $extensions = ['jpg', 'jpeg', 'png', 'webp', 'JPG', 'JPEG', 'PNG', 'WEBP'];
        $images = [];

        $recursive = in_array($folderPath, ['public', 'private']);

        if ($recursive) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile() && in_array($file->getExtension(), $extensions)) {
                    $relativePath = str_replace($basePath . '/', '', $file->getPathname());
                    $size = $file->getSize();
                    $images[] = [
                        'name' => $relativePath,
                        'full_path' => $file->getPathname(),
                        'size_mb' => round($size / 1024 / 1024, 2),
                        'need_compress' => $size > (1.5 * 1024 * 1024)
                    ];
                }
            }
        } else {
            $pattern = $basePath . '/*.{' . implode(',', $extensions) . '}';
            $files = glob($pattern, GLOB_BRACE);
            foreach ($files as $file) {
                $size = filesize($file);
                $images[] = [
                    'name' => basename($file),
                    'full_path' => $file,
                    'size_mb' => round($size / 1024 / 1024, 2),
                    'need_compress' => $size > (1.5 * 1024 * 1024)
                ];
            }
        }

        return $images;
    }

    // ================= PROSES KOMPRESI (AJAX) =================
    public function proses(Request $request)
    {
        if (!$this->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'files' => 'required|array',
            'files.*' => 'string',
            'folder' => 'required|string'
        ]);

        $folderPath = $request->input('folder');
        $baseDir = storage_path('app/' . $folderPath);
        $recursive = in_array($folderPath, ['public', 'private']);

        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $maxSizeBytes = 1.5 * 1024 * 1024;
        $results = [];

        foreach ($request->input('files') as $relativeName) {
            $filePath = $baseDir . '/' . $relativeName;

            if (!file_exists($filePath)) {
                $results[] = ['name' => $relativeName, 'status' => 'skip', 'message' => 'File tidak ditemukan'];
                continue;
            }

            $currentSize = filesize($filePath);
            if ($currentSize <= $maxSizeBytes) {
                $results[] = ['name' => $relativeName, 'status' => 'skip', 'message' => 'Sudah ≤ 1.5 MB'];
                continue;
            }

            $success = $this->compressImageKeepFormat($filePath, $maxSizeBytes);

            if ($success) {
                $newSize = filesize($filePath);
                $results[] = [
                    'name' => $relativeName,
                    'status' => 'success',
                    'old_mb' => round($currentSize / 1024 / 1024, 2),
                    'new_mb' => round($newSize / 1024 / 1024, 2)
                ];
            } else {
                $results[] = ['name' => $relativeName, 'status' => 'failed', 'message' => 'Gagal kompres'];
            }
        }

        return response()->json(['results' => $results]);
    }

    // ================= FUNGSI KOMPRES GAMBAR (FORMAT TETAP) =================
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
                    // Lossless dulu
                    imagepng($src, $tempPath, 9);
                    clearstatcache();
                    if (filesize($tempPath) <= $maxSizeBytes) {
                        rename($tempPath, $filePath);
                        imagedestroy($src);
                        return true;
                    }
                    // Resize bertahap
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