<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Helpers\HsrmRole;
use App\Models\HSRM\HsrmCertificate;
use App\Models\HSRM\HsrmEquipment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class HsrmFileController extends Controller
{
    public function download($type, $id, $old_index = null)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }

        if ($old_index === null) {
            $old_index = request('old_index');
        }

        if ($type === 'certificate') {
            $item = HsrmCertificate::with('area')->findOrFail($id);
            $this->authorizeView($item);
            $path = $this->getPath($item, $old_index);
        } elseif ($type === 'equipment') {
            $item = HsrmEquipment::with('area')->findOrFail($id);
            $this->authorizeView($item);
            $path = $this->getPath($item, $old_index);
        } else {
            abort(404, 'Invalid type');
        }

        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'File not found');
        }

        $fullPath = Storage::disk('public')->path($path);
        if (!is_readable($fullPath)) {
            abort(500, 'File is not readable.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf'  => 'application/pdf',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
        ];
        $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

        // Gambar dibuka lewat halaman viewer (bisa rotasi & zoom).
        // Viewer memuat gambar aslinya dari URL yang sama + ?raw=1.
        // PDF tetap di-stream langsung seperti sebelumnya.
        if (str_starts_with($mimeType, 'image/') && !request()->boolean('raw')) {
            // Simpan rotasi permanen hanya untuk file aktif (bukan arsip old_index)
            // dan hanya untuk user yang punya hak tulis.
            $canRotate = false;
            if ($old_index === null) {
                try {
                    HsrmRole::abortUnlessCanWrite();
                    $canRotate = true;
                } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                    $canRotate = false;
                }
            }
            return view('hsrm.image_viewer', [
                'src'       => request()->fullUrlWithQuery(['raw' => 1, 'v' => filemtime($fullPath)]),
                'filename'  => basename($path),
                'rotateUrl' => $canRotate ? route('hsrm.file.rotate', ['type' => $type, 'id' => $id]) : null,
            ]);
        }

        // Semua tipe file (termasuk PDF) di-stream langsung dari disk,
        // bukan dibungkus base64 ke dalam halaman HTML. Base64+<embed>
        // (cara lama) sering gagal render blank di Chrome untuk file
        // yang lumayan besar karena keterbatasan data: URI di <embed>.
        // Streaming biasa dengan header inline lebih stabil dan lebih
        // ringan (tidak perlu load seluruh file ke memory PHP sebagai
        // base64 string).
        return response()->stream(function () use ($fullPath) {
            readfile($fullPath);
        }, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
            'Content-Length' => filesize($fullPath),
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Simpan rotasi gambar secara permanen (overwrite file aktif).
     * Body: degrees = 90 | 180 | 270 (searah jarum jam).
     */
    public function rotate($type, $id)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized');
        }
        HsrmRole::abortUnlessCanWrite('You are not authorized to modify this file.');

        $degrees = (int) request('degrees');
        if (!in_array($degrees, [90, 180, 270], true)) {
            return response()->json(['message' => 'Invalid rotation.'], 422);
        }

        if ($type === 'certificate') {
            $item = HsrmCertificate::with('area')->findOrFail($id);
        } elseif ($type === 'equipment') {
            $item = HsrmEquipment::with('area')->findOrFail($id);
        } else {
            abort(404, 'Invalid type');
        }
        $this->authorizeView($item);

        $path = $this->getPath($item, null);
        if (!$path || !Storage::disk('public')->exists($path)) {
            abort(404, 'File not found');
        }

        $fullPath = Storage::disk('public')->path($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) || !extension_loaded('gd')) {
            return response()->json(['message' => 'File type cannot be rotated.'], 422);
        }

        $loaders = ['jpg' => 'imagecreatefromjpeg', 'jpeg' => 'imagecreatefromjpeg', 'png' => 'imagecreatefrompng', 'gif' => 'imagecreatefromgif', 'webp' => 'imagecreatefromwebp'];
        $img = @$loaders[$ext]($fullPath);
        if (!$img) {
            return response()->json(['message' => 'Failed to read image.'], 500);
        }

        // Browser menampilkan JPEG sesuai EXIF; GD mengabaikannya. Samakan dulu.
        if (in_array($ext, ['jpg', 'jpeg'], true) && function_exists('exif_read_data')) {
            $exif = @exif_read_data($fullPath);
            $fix = [3 => 180, 6 => -90, 8 => 90][$exif['Orientation'] ?? 1] ?? 0;
            if ($fix) {
                $img = imagerotate($img, $fix, 0);
            }
        }

        // imagerotate berputar berlawanan arah jarum jam -> konversi ke searah jarum jam
        $angle = 360 - $degrees;
        if ($ext === 'png' || $ext === 'webp' || $ext === 'gif') {
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

        $saved = match ($ext) {
            'jpg', 'jpeg' => imagejpeg($rotated, $fullPath, 90),
            'png'         => imagepng($rotated, $fullPath),
            'gif'         => imagegif($rotated, $fullPath),
            'webp'        => imagewebp($rotated, $fullPath, 90),
        };
        imagedestroy($rotated);

        if (!$saved) {
            return response()->json(['message' => 'Failed to save image.'], 500);
        }
        clearstatcache(true, $fullPath);

        return response()->json(['ok' => true]);
    }

    private function getPath($item, $old_index = null)
    {
        if ($old_index !== null && isset($item->old_attachments[$old_index])) {
            $old = $item->old_attachments[$old_index];
            return $old['path'] ?? null;
        }
        return $item->attachment_path ?? $item->photo_path ?? null;
    }

    private function authorizeView($item)
    {
        $user = Auth::user();
        if (HsrmRole::canViewAll()) {
            return;
        }
        $areaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
        if (!in_array($item->area_id, $areaIds)) {
            abort(403, 'You do not have access to this file.');
        }
    }
}