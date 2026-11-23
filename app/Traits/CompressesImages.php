<?php

namespace App\Traits;

trait CompressesImages
{
    /**
     * Kompres gambar sampai <= $maxSizeMB. Kalau format bukan gambar (pdf/doc dll),
     * cukup dipindah apa adanya oleh pemanggil — trait ini cuma urus gambar.
     */
    protected function compressImage($sourcePath, $destPath, $maxSizeMB = 1.5)
    {
        if (!file_exists($sourcePath)) return false;
        $info = getimagesize($sourcePath);
        if (!$info) return false;

        $mime = $info['mime'];
        $maxSizeBytes = $maxSizeMB * 1024 * 1024;

        if (filesize($sourcePath) <= $maxSizeBytes) {
            if ($sourcePath !== $destPath) copy($sourcePath, $destPath);
            return true;
        }

        $src = match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($sourcePath),
            'image/png'  => imagecreatefrompng($sourcePath),
            'image/webp' => imagecreatefromwebp($sourcePath),
            default      => null,
        };
        if (!$src) return false;

        if ($mime === 'image/png') {
            [$width, $height] = [imagesx($src), imagesy($src)];
            $jpeg = imagecreatetruecolor($width, $height);
            imagefill($jpeg, 0, 0, imagecolorallocate($jpeg, 255, 255, 255));
            imagecopy($jpeg, $src, 0, 0, 0, 0, $width, $height);
            imagedestroy($src);
            $src = $jpeg;
            $mime = 'image/jpeg';
        }

        $quality = 90;
        $minQuality = 20;
        $tempPath = $destPath . '.tmp';
        $success = false;

        while ($quality >= $minQuality) {
            $mime === 'image/webp'
                ? imagewebp($src, $tempPath, $quality)
                : imagejpeg($src, $tempPath, $quality);
            clearstatcache();
            if (filesize($tempPath) <= $maxSizeBytes) {
                rename($tempPath, $destPath);
                $success = true;
                break;
            }
            $quality -= 5;
        }
        if (!$success && file_exists($tempPath)) {
            rename($tempPath, $destPath);
            $success = true;
        }
        imagedestroy($src);
        return $success;
    }

    /**
     * Simpan file upload (foto atau dokumen) ke folder tujuan, kompres kalau gambar.
     * $folder relatif terhadap disk 'private', mis. 'messenger/foto_barang'.
     * Return: nama file akhir yang tersimpan.
     */
    protected function storeCompressedOrMove($file, string $folder, string $baseName): string
    {
        $ext = $file->getClientOriginalExtension();
        $tempPath = $file->storeAs('messenger/temp', 'temp_' . time() . '_' . uniqid() . '.' . $ext, 'private');
        $fullTempPath = \Storage::disk('private')->path($tempPath);

        if (in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp'])) {
            $finalName = $baseName . '.jpg';
            $finalPath = $folder . '/' . $finalName;
            $fullFinalPath = \Storage::disk('private')->path($finalPath);

            if ($this->compressImage($fullTempPath, $fullFinalPath, 1.5)) {
                \Storage::disk('private')->delete($tempPath);
                return $finalName;
            }
        }

        $finalName = $baseName . '.' . $ext;
        \Storage::disk('private')->move($tempPath, $folder . '/' . $finalName);
        return $finalName;
    }
}
