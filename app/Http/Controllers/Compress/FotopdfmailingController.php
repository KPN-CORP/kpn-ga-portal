<?php

namespace App\Http\Controllers\Compress;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FotopdfmailingController extends Controller
{
    protected float $maxSizeMb = 1.5;

    protected array $imageExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    protected string $pdfExtension = 'pdf';

    protected function maxSizeBytes(): int
    {
        return (int) ($this->maxSizeMb * 1024 * 1024);
    }

    /**
     * Disk ad-hoc yang root-nya BENAR-BENAR storage/app (bukan
     * storage/app/private, yang jadi default disk 'local' sejak Laravel 11).
     * Dengan ini, allFiles('')/directories('') mencakup KEDUA subfolder
     * bawaan Laravel: storage/app/private/** dan storage/app/public/**,
     * jadi "Kompres Seluruh storage/app" benar-benar menyisir semuanya.
     */
    protected function disk(): \Illuminate\Filesystem\FilesystemAdapter
    {
        return Storage::build([
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ]);
    }

    /**
     * Cegah path traversal (../) dan bersihkan slash.
     */
    protected function sanitizePath(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $path = str_replace('\\', '/', $path);
        $path = trim($path, '/');

        if (str_contains($path, '..')) {
            return null;
        }

        // path kosong ('') artinya root storage/app — valid, bukan error
        return $path;
    }

    protected function isAllowedExt(string $ext): bool
    {
        $ext = strtolower($ext);

        return in_array($ext, $this->imageExtensions, true) || $ext === $this->pdfExtension;
    }

    /**
     * Halaman utama kompres: scan SEMUA file secara REKURSIF (termasuk folder
     * paling dalam/nested) di dalam folder terpilih — gambar & PDF sekaligus.
     */
    public function index(Request $request)
    {
        $selectedFolder = $this->sanitizePath($request->get('folder', 'public/mailing-foto'))
            ?? 'public/mailing-foto';

        // Root storage/app ('') selalu valid — Storage::exists('') bisa false
        // di beberapa driver Flysystem meski root-nya jelas ada, jadi dilewati.
        if ($selectedFolder !== '' && ! $this->disk()->exists($selectedFolder)) {
            return redirect()
                ->route('kompres.browse')
                ->with('error', 'Folder tidak ditemukan: '.$selectedFolder);
        }

        $allPaths = $this->disk()->allFiles($selectedFolder);

        $files = [];
        $needCompress = 0;

        foreach ($allPaths as $relPath) {
            $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));

            if (! $this->isAllowedExt($ext)) {
                continue;
            }

            $size = $this->disk()->size($relPath);
            $need = $size > $this->maxSizeBytes();

            if ($need) {
                $needCompress++;
            }

            $subDir = trim(str_replace($selectedFolder, '', dirname($relPath)), '/');

            $files[] = [
                'path' => $relPath, // identifier unik yang dipakai untuk proses/hapus
                'name' => basename($relPath),
                'sub_dir' => $subDir === '.' ? '' : $subDir,
                'type' => $ext === $this->pdfExtension ? 'pdf' : 'image',
                'size_mb' => round($size / 1048576, 2),
                'need_compress' => $need,
            ];
        }

        return view('kompres.kompres', [
            'selectedFolder' => $selectedFolder,
            'files' => $files,
            'totalFiles' => count($files),
            'needCompress' => $needCompress,
        ]);
    }

    /**
     * Telusuri folder satu level (dengan navigasi ke subfolder) untuk memilih
     * folder mana yang mau dikompres, dan untuk hapus/preview cepat per file.
     */
    public function browse(Request $request)
    {
        $currentPath = $this->sanitizePath($request->get('path', '')) ?? '';

        if ($currentPath !== '' && ! $this->disk()->exists($currentPath)) {
            $this->disk()->makeDirectory($currentPath);
        }

        $directories = collect($this->disk()->directories($currentPath))
            ->map(fn ($d) => basename($d))
            ->sort()
            ->values();

        $allFiles = collect($this->disk()->files($currentPath));

        $mapFile = function (string $f) {
            $size = $this->disk()->size($f);

            return [
                'path' => $f,
                'name' => basename($f),
                'url' => route('kompres.image', ['path' => $f]),
                'size_mb' => round($size / 1048576, 2),
                'need_compress' => $size > $this->maxSizeBytes(),
            ];
        };

        $images = $allFiles
            ->filter(fn ($f) => in_array(strtolower(pathinfo($f, PATHINFO_EXTENSION)), $this->imageExtensions, true))
            ->map($mapFile)
            ->values();

        $pdfs = $allFiles
            ->filter(fn ($f) => strtolower(pathinfo($f, PATHINFO_EXTENSION)) === $this->pdfExtension)
            ->map($mapFile)
            ->values();

        return view('kompres.browse', [
            'currentPath' => $currentPath,
            'directories' => $directories,
            'images' => $images,
            'pdfs' => $pdfs,
        ]);
    }

    /**
     * Stream file gambar/PDF untuk thumbnail & preview.
     */
    public function showImage(Request $request)
    {
        $path = $this->sanitizePath($request->get('path'));

        if (! $path || ! $this->disk()->exists($path)) {
            abort(404);
        }

        return response()->file($this->disk()->path($path));
    }

    /**
     * Proses kompres batch (AJAX). Mendukung gambar & PDF sekaligus,
     * termasuk file yang berada di folder paling dalam.
     */
    public function proses(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'string',
        ]);

        $results = [];

        foreach ($request->input('files') as $relPath) {
            $relPath = $this->sanitizePath($relPath);
            $labelName = $relPath ? basename($relPath) : '(path tidak valid)';

            if (! $relPath || ! $this->disk()->exists($relPath)) {
                $results[] = ['name' => $labelName, 'status' => 'failed', 'message' => 'File tidak ditemukan'];

                continue;
            }

            $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));
            $fullPath = $this->disk()->path($relPath);
            $oldSize = filesize($fullPath);

            try {
                if ($oldSize <= $this->maxSizeBytes()) {
                    $results[] = [
                        'name' => $labelName,
                        'status' => 'skip',
                        'message' => 'Sudah ≤ '.$this->maxSizeMb.' MB',
                    ];

                    continue;
                }

                if (in_array($ext, $this->imageExtensions, true)) {
                    $newSize = $this->compressImage($fullPath, $ext);
                } elseif ($ext === $this->pdfExtension) {
                    $newSize = $this->compressPdf($fullPath);
                } else {
                    $results[] = ['name' => $labelName, 'status' => 'skip', 'message' => 'Tipe file tidak didukung'];

                    continue;
                }

                if ($newSize === null) {
                    $results[] = ['name' => $labelName, 'status' => 'failed', 'message' => 'Gagal dikompres'];

                    continue;
                }

                $results[] = [
                    'name' => $labelName,
                    'status' => 'success',
                    'old_mb' => round($oldSize / 1048576, 2),
                    'new_mb' => round($newSize / 1048576, 2),
                ];
            } catch (\Throwable $e) {
                Log::error('Kompres gagal: '.$relPath.' - '.$e->getMessage());
                $results[] = ['name' => $labelName, 'status' => 'failed', 'message' => $e->getMessage()];
            }
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Hapus file (satu file atau bulk/terpilih sekaligus), termasuk file
     * yang ada di folder paling dalam.
     */
    public function hapus(Request $request)
    {
        $request->validate([
            'files' => 'required|array|min:1',
            'files.*' => 'string',
        ]);

        $results = [];

        foreach ($request->input('files') as $relPath) {
            $relPath = $this->sanitizePath($relPath);
            $labelName = $relPath ? basename($relPath) : '(path tidak valid)';

            if (! $relPath || ! $this->disk()->exists($relPath)) {
                $results[] = ['name' => $labelName, 'status' => 'failed', 'message' => 'File tidak ditemukan'];

                continue;
            }

            $ext = strtolower(pathinfo($relPath, PATHINFO_EXTENSION));

            if (! $this->isAllowedExt($ext)) {
                $results[] = ['name' => $labelName, 'status' => 'failed', 'message' => 'Tipe file tidak diizinkan untuk dihapus lewat modul ini'];

                continue;
            }

            try {
                $this->disk()->delete($relPath);
                $results[] = ['name' => $labelName, 'status' => 'success', 'message' => 'Terhapus'];
            } catch (\Throwable $e) {
                Log::error('Hapus gagal: '.$relPath.' - '.$e->getMessage());
                $results[] = ['name' => $labelName, 'status' => 'failed', 'message' => $e->getMessage()];
            }
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Kompres gambar (jpg/jpeg/png/webp): resize jika terlalu besar dimensinya,
     * lalu turunkan kualitas bertahap sampai ukurannya di bawah batas.
     */
    protected function compressImage(string $fullPath, string $ext): ?int
    {
        $info = @getimagesize($fullPath);

        if (! $info) {
            return null;
        }

        $image = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($fullPath),
            'png' => @imagecreatefrompng($fullPath),
            'webp' => @imagecreatefromwebp($fullPath),
            default => null,
        };

        if (! $image) {
            return null;
        }

        // Kecilkan dimensi dulu kalau sisi terpanjang > 2000px
        $width = imagesx($image);
        $height = imagesy($image);
        $maxDimension = 2000;

        if (max($width, $height) > $maxDimension) {
            $ratio = $maxDimension / max($width, $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            if ($ext === 'png') {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        $quality = 85;

        do {
            switch ($ext) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($image, $fullPath, $quality);
                    break;
                case 'png':
                    // skala kualitas 0-100 -> level kompresi PNG 0-9 (9 = paling padat)
                    imagepng($image, $fullPath, (int) round((100 - $quality) / 11.1));
                    break;
                case 'webp':
                    imagewebp($image, $fullPath, $quality);
                    break;
            }

            clearstatcache(true, $fullPath);
            $currentSize = filesize($fullPath);
            $quality -= 10;
        } while ($currentSize > $this->maxSizeBytes() && $quality >= 30);

        imagedestroy($image);
        clearstatcache(true, $fullPath);

        return filesize($fullPath);
    }

    /**
     * Kompres PDF memakai Ghostscript (binary `gs` harus terpasang di server).
     */
    protected function compressPdf(string $fullPath): ?int
    {
        $gsBinary = trim((string) shell_exec('command -v gs'));

        if ($gsBinary === '') {
            throw new \RuntimeException('Ghostscript (gs) tidak ditemukan di server, PDF tidak bisa dikompres.');
        }

        $tempPath = $fullPath.'.compressed.pdf';

        $cmd = sprintf(
            '%s -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile=%s %s 2>&1',
            escapeshellcmd($gsBinary),
            escapeshellarg($tempPath),
            escapeshellarg($fullPath)
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0 || ! file_exists($tempPath) || filesize($tempPath) === 0) {
            @unlink($tempPath);
            throw new \RuntimeException('Ghostscript gagal memproses PDF: '.implode(' ', $output));
        }

        // Kadang hasil gs lebih besar dari aslinya (PDF sudah optimal) -> pakai yang lebih kecil saja
        if (filesize($tempPath) < filesize($fullPath)) {
            rename($tempPath, $fullPath);
        } else {
            @unlink($tempPath);
        }

        clearstatcache(true, $fullPath);

        return filesize($fullPath);
    }
}