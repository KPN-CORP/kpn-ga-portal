<?php

namespace App\Http\Controllers;

use App\Models\TrackRFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class TrackFotoController extends Controller
{
    /**
     * Menampilkan gambar dari berbagai kemungkinan lokasi
     */
    public function view($id)
    {
        try {
            $foto = TrackRFoto::with('document')->findOrFail($id);
            
            Log::info('Mencoba menampilkan foto TrackR:', [
                'id' => $id,
                'path_db' => $foto->path,
                'nama_file' => $foto->nama_file,
                'document_id' => $foto->track_r_document_id
            ]);
            
            // Cek otorisasi
            $document = $foto->document;
            if ($document) {
                $user = auth()->user();
                if (!$user || ($user->id != $document->pengirim_id && $user->id != $document->penerima_id)) {
                    Log::warning('Akses tidak sah ke foto: ' . $id);
                    return $this->getPlaceholderImage('unauthorized');
                }
            }
            
            // Cari file - PERBAIKAN: Tambahkan lokasi private
            $filePath = $this->findFile($foto);
            
            if ($filePath) {
                Log::info('Foto TrackR ditemukan di: ' . $filePath);
                
                // Untuk file private, kita perlu membaca file dan mengembalikan response
                $fileContent = file_get_contents($filePath);
                $mimeType = $this->getMimeType($filePath);
                
                return response($fileContent, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Content-Disposition', 'inline; filename="' . $foto->nama_file . '"')
                    ->header('Cache-Control', 'public, max-age=86400');
            }
            
            Log::error('Foto TrackR tidak ditemukan untuk ID: ' . $id);
            return $this->getPlaceholderImage($foto->tipe);
            
        } catch (\Exception $e) {
            Log::error('Error menampilkan foto TrackR: ' . $e->getMessage());
            return $this->getPlaceholderImage();
        }
    }
    
    /**
     * Mencari file di berbagai lokasi - PERBAIKAN: Tambah lokasi private
     */
    private function findFile($foto)
    {
        $pathsToTry = [];
        $fileName = basename($foto->path);
        $documentId = $foto->track_r_document_id;
        
        // 1. LOKASI PRIVATE (ini yang paling mungkin)
        $pathsToTry[] = storage_path('app/private/' . $foto->path);
        $pathsToTry[] = storage_path('app/private/Track/' . $documentId . '/' . $fileName);
        $pathsToTry[] = storage_path('app/private/track-r/' . $documentId . '/' . $fileName);
        $pathsToTry[] = storage_path('app/private/fotos/' . $documentId . '/' . $fileName);
        
        // 2. LOKASI PUBLIC (fallback)
        $pathsToTry[] = storage_path('app/public/' . $foto->path);
        $pathsToTry[] = storage_path('app/public/Track/' . $documentId . '/' . $fileName);
        $pathsToTry[] = storage_path('app/public/track-r/' . $documentId . '/' . $fileName);
        $pathsToTry[] = storage_path('app/public/fotos/' . $documentId . '/' . $fileName);
        
        // 3. Path dari database langsung
        $pathsToTry[] = $foto->path;
        
        // 4. Base path
        $pathsToTry[] = storage_path('app/private/' . $fileName);
        $pathsToTry[] = storage_path('app/public/' . $fileName);
        
        // Filter path unik
        $pathsToTry = array_unique($pathsToTry);
        
        // Coba setiap path
        foreach ($pathsToTry as $path) {
            if (!empty($path) && file_exists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * Download foto - PERBAIKAN: handle private file
     */
    public function download($id)
    {
        try {
            $foto = TrackRFoto::with('document')->findOrFail($id);
            
            // Cek otorisasi
            $document = $foto->document;
            if ($document) {
                $user = auth()->user();
                if (!$user || ($user->id != $document->pengirim_id && $user->id != $document->penerima_id)) {
                    abort(403, 'Unauthorized access');
                }
            }
            
            // Cari file
            $filePath = $this->findFile($foto);
            
            if ($filePath) {
                Log::info('Download foto TrackR: ' . $filePath);
                return response()->download($filePath, $foto->nama_file);
            }
            
            abort(404, 'File tidak ditemukan');
            
        } catch (\Exception $e) {
            Log::error('Error download foto: ' . $e->getMessage());
            abort(404, 'File tidak ditemukan');
        }
    }
    
    /**
     * Mendapatkan mime type file
     */
    private function getMimeType($path)
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
        ];
        
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return $mimeTypes[$ext] ?? 'application/octet-stream';
    }
    
    /**
     * Menghasilkan placeholder image
     */
    private function getPlaceholderImage($type = null)
    {
        $width = 500;
        $height = 350;
        
        if (extension_loaded('gd')) {
            $img = imagecreatetruecolor($width, $height);
            
            if ($type === 'unauthorized') {
                $bg = imagecolorallocate($img, 255, 235, 235);
                $textColor = imagecolorallocate($img, 200, 0, 0);
                $text = "Akses Tidak Diizinkan";
            } else {
                $bg = imagecolorallocate($img, 245, 245, 245);
                $textColor = imagecolorallocate($img, 100, 100, 100);
                $text = "Gambar Tidak Ditemukan";
            }
            
            imagefill($img, 0, 0, $bg);
            
            $iconColor = imagecolorallocate($img, 200, 200, 200);
            imagefilledrectangle($img, 200, 120, 300, 180, $iconColor);
            
            imagestring($img, 5, 150, 220, $text, $textColor);
            imagestring($img, 3, 120, 250, "Klik download untuk mengambil file", $textColor);
            
            ob_start();
            imagepng($img);
            $imageData = ob_get_clean();
            imagedestroy($img);
            
            return response($imageData)
                    ->header('Content-Type', 'image/png')
                    ->header('X-Placeholder', 'true');
        }
        
        // Fallback SVG
        $color = $type === 'unauthorized' ? '#ffcccc' : '#f0f0f0';
        $textColor = $type === 'unauthorized' ? '#cc0000' : '#666666';
        $text = $type === 'unauthorized' ? 'Akses Tidak Diizinkan' : 'Gambar Tidak Ditemukan';
        
        $svg = <<<SVG
        <svg width="$width" height="$height" xmlns="http://www.w3.org/2000/svg">
            <rect width="$width" height="$height" fill="$color" stroke="#cccccc" stroke-width="2"/>
            <text x="250" y="200" font-family="Arial" font-size="16" fill="$textColor" text-anchor="middle">$text</text>
            <text x="250" y="250" font-family="Arial" font-size="12" fill="$textColor" text-anchor="middle">Klik download untuk mengambil file</text>
        </svg>
        SVG;
        
        return response($svg)
                ->header('Content-Type', 'image/svg+xml')
                ->header('X-Placeholder', 'true');
    }
}