<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class ImageHelper
{
    /**
     * Kompres gambar ke maksimal 1.5 MB dan simpan ke storage/private
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $path
     * @param int $maxSize KB
     * @return string|false
     */
    public static function compressAndStore($file, $path, $maxSize = 1500)
    {
        try {
            // Buat ImageManager dengan driver GD
            $manager = new ImageManager(new GdDriver());
            $image = $manager->read($file->getRealPath());

            // Kualitas awal 90%
            $quality = 90;
            $maxKb = $maxSize;

            // Loop kompresi dengan quality
            do {
                $tempPath = tempnam(sys_get_temp_dir(), 'img');
                $image->save($tempPath, $quality);
                $sizeKb = filesize($tempPath) / 1024;
                $quality -= 5;
            } while ($sizeKb > $maxKb && $quality > 20);

            // Jika masih terlalu besar, resize
            if ($sizeKb > $maxKb) {
                $width = $image->width();
                $height = $image->height();
                $scale = sqrt(($maxKb * 1024) / filesize($tempPath));
                $newWidth = intval($width * $scale);
                $newHeight = intval($height * $scale);
                $image->resize($newWidth, $newHeight);
                $image->save($tempPath, 75);
            }

            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $fullPath = $path . '/' . $filename;

            Storage::disk('private')->put($fullPath, file_get_contents($tempPath));

            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            return $fullPath;

        } catch (\Exception $e) {
            \Log::error('ImageHelper compressAndStore error: ' . $e->getMessage());
            return false;
        }
    }

    public static function deleteImage($path)
    {
        if ($path && Storage::disk('private')->exists($path)) {
            Storage::disk('private')->delete($path);
            return true;
        }
        return false;
    }
}