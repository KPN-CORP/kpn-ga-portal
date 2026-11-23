<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Exception;

class HsrmFileHelper
{
    /**
     * Store file (image compressed, PDF not) to storage/public
     * 
     * @return string|false  Relative path or false on failure
     */
    public static function storeAttachment(UploadedFile $file, $folder)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $relativePath = 'hsrm/' . $folder . '/' . $filename;

        try {
            // Ensure directory exists
            $directory = dirname(storage_path('app/public/' . $relativePath));
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // For images: compress
            if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $tempPath = self::compressImage($file);
                if ($tempPath && file_exists($tempPath)) {
                    $stored = Storage::disk('public')->put($relativePath, file_get_contents($tempPath));
                    @unlink($tempPath);
                    if ($stored && Storage::disk('public')->exists($relativePath)) {
                        return $relativePath;
                    }
                    \Log::error('Failed to store image: ' . $relativePath);
                    return false;
                }
                \Log::warning('Image compression failed, falling back to normal store for: ' . $file->getClientOriginalName());
            }

            // For PDF or others: store using put (more reliable)
            $content = file_get_contents($file->getRealPath());
            if ($content === false) {
                \Log::error('Failed to read file content: ' . $file->getClientOriginalName());
                return false;
            }

            $stored = Storage::disk('public')->put($relativePath, $content);
            if ($stored && Storage::disk('public')->exists($relativePath)) {
                return $relativePath;
            }

            \Log::error('Storage put failed for: ' . $relativePath);
            return false;
        } catch (Exception $e) {
            \Log::error('storeAttachment error: ' . $e->getMessage() . ' for file: ' . $file->getClientOriginalName());
            return false;
        }
    }

    /**
     * Compress image to max 1.5 MB
     */
    private static function compressImage($file)
    {
        try {
            $manager = new ImageManager(new GdDriver());
            $image = $manager->read($file->getRealPath());

            $quality = 90;
            $maxKb = 1500;
            $tempPath = null;
            do {
                if ($tempPath && file_exists($tempPath)) {
                    @unlink($tempPath);
                }
                $tempPath = tempnam(sys_get_temp_dir(), 'img');
                $image->save($tempPath, $quality);
                $sizeKb = filesize($tempPath) / 1024;
                $quality -= 5;
            } while ($sizeKb > $maxKb && $quality > 20);

            if ($sizeKb > $maxKb) {
                $width = $image->width();
                $height = $image->height();
                $scale = sqrt(($maxKb * 1024) / filesize($tempPath));
                $newWidth = intval($width * $scale);
                $newHeight = intval($height * $scale);
                $image->resize($newWidth, $newHeight);
                $image->save($tempPath, 75);
            }
            return $tempPath;
        } catch (Exception $e) {
            \Log::error('Compress image error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Archive old file to subfolder 'old'
     */
    public static function archiveOldAttachment($oldPath, $folder, $existingOlds = [])
    {
        if (empty($oldPath)) {
            return $existingOlds;
        }

        $fullOldPath = storage_path('app/public/' . $oldPath);
        if (!file_exists($fullOldPath)) {
            \Log::warning('Archive old attachment: file not found at ' . $fullOldPath);
            return $existingOlds;
        }

        $timestamp = now()->format('Y-m-d H:i:s');
        $dirname = dirname($oldPath);
        $basename = basename($oldPath);
        $oldFolder = $dirname . '/old';
        $newPath = $oldFolder . '/' . $timestamp . '_' . $basename;
        $fullNewPath = storage_path('app/public/' . $newPath);

        if (!file_exists(dirname($fullNewPath))) {
            mkdir(dirname($fullNewPath), 0755, true);
        }

        if (rename($fullOldPath, $fullNewPath)) {
            $existingOlds[] = [
                'path' => $newPath,
                'archived_at' => $timestamp,
                'original_name' => $basename,
            ];
            \Log::info('Archived old attachment: ' . $oldPath . ' -> ' . $newPath);
        } else {
            \Log::error('Failed to archive old attachment: ' . $oldPath);
        }

        return $existingOlds;
    }
}