<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\HelpTiket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class HelpTiketPDFController extends Controller
{
    /**
     * Generate PDF untuk detail tiket
     */
    public function download(HelpTiket $tiket)
    {
        // Set memory limit lebih besar
        ini_set('memory_limit', '512M');
        set_time_limit(300);
        
        // Load relasi yang diperlukan
        $tiket->load([
            'pelapor.user',
            'ditugaskanKe.user',
            'kategori',
            'bisnisUnit',
            'logStatus.pengguna.user',
            'komentar.pengguna.user',
            'lampiran.pengguna'
        ]);

        // Encode gambar ke base64 dengan handling error yang lebih baik
        $lampiranBase64 = [];
        
        foreach ($tiket->lampiran as $lampiran) {
            // Cek apakah file adalah gambar
            $isImage = str_contains($lampiran->tipe_file, 'image') || 
                       in_array(strtolower(pathinfo($lampiran->nama_file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
            
            if ($isImage) {
                // Path file
                $path = storage_path('app/private/' . $lampiran->path_file);
                
                Log::info('Memproses gambar:', [
                    'id' => $lampiran->id,
                    'nama' => $lampiran->nama_file,
                    'path' => $path,
                    'exists' => file_exists($path) ? 'YES' : 'NO',
                    'ukuran' => $lampiran->ukuran_file . ' bytes'
                ]);
                
                if (file_exists($path)) {
                    try {
                        // Baca file
                        $imageData = file_get_contents($path);
                        
                        if ($imageData) {
                            // Coba kompres gambar jika terlalu besar (> 200KB)
                            $fileSize = strlen($imageData);
                            
                            if ($fileSize > 200000) { // > 200KB
                                Log::info('Gambar besar, dikompres: ' . $fileSize . ' bytes');
                                
                                try {
                                    // Cek apakah GD library tersedia
                                    if (extension_loaded('gd')) {
                                        // Buat image resource berdasarkan tipe
                                        $img = null;
                                        $mimeType = $lampiran->tipe_file;
                                        
                                        if (str_contains($mimeType, 'jpeg') || str_contains($mimeType, 'jpg') || str_contains($mimeType, 'jfif')) {
                                            $img = @imagecreatefromjpeg($path);
                                            if ($img) {
                                                // Kompres JPEG dengan kualitas 40%
                                                ob_start();
                                                imagejpeg($img, null, 40);
                                                $compressedData = ob_get_clean();
                                                imagedestroy($img);
                                                
                                                if ($compressedData) {
                                                    $base64 = 'data:image/jpeg;base64,' . base64_encode($compressedData);
                                                    Log::info('Kompres JPEG berhasil: ' . strlen($compressedData) . ' bytes');
                                                } else {
                                                    throw new \Exception('Gagal kompres JPEG');
                                                }
                                            } else {
                                                throw new \Exception('Gagal membuat resource JPEG');
                                            }
                                        }
                                        elseif (str_contains($mimeType, 'png')) {
                                            $img = @imagecreatefrompng($path);
                                            if ($img) {
                                                // Kompres PNG
                                                ob_start();
                                                imagepng($img, null, 9); // 9 = kompresi maksimal
                                                $compressedData = ob_get_clean();
                                                imagedestroy($img);
                                                
                                                if ($compressedData) {
                                                    $base64 = 'data:image/png;base64,' . base64_encode($compressedData);
                                                    Log::info('Kompres PNG berhasil: ' . strlen($compressedData) . ' bytes');
                                                } else {
                                                    throw new \Exception('Gagal kompres PNG');
                                                }
                                            } else {
                                                throw new \Exception('Gagal membuat resource PNG');
                                            }
                                        }
                                        else {
                                            // Format lain, pakai original
                                            $base64 = 'data:' . $lampiran->tipe_file . ';base64,' . base64_encode($imageData);
                                        }
                                    } else {
                                        // GD library tidak tersedia, pakai original
                                        Log::warning('GD library tidak tersedia, gunakan original');
                                        $base64 = 'data:' . $lampiran->tipe_file . ';base64,' . base64_encode($imageData);
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Gagal kompres: ' . $e->getMessage());
                                    // Fallback ke original
                                    $base64 = 'data:' . $lampiran->tipe_file . ';base64,' . base64_encode($imageData);
                                }
                            } else {
                                // Gambar kecil, pakai original
                                $base64 = 'data:' . $lampiran->tipe_file . ';base64,' . base64_encode($imageData);
                                Log::info('Gambar kecil, original: ' . $fileSize . ' bytes');
                            }
                            
                            $lampiranBase64[$lampiran->id] = $base64;
                            Log::info('BERHASIL encode: ' . $lampiran->nama_file . ' dengan ID: ' . $lampiran->id);
                        } else {
                            Log::warning('Gagal baca file: ' . $lampiran->nama_file);
                            $lampiranBase64[$lampiran->id] = null;
                        }
                    } catch (\Exception $e) {
                        Log::error('Error processing file: ' . $e->getMessage());
                        $lampiranBase64[$lampiran->id] = null;
                    }
                } else {
                    Log::warning('File tidak ditemukan: ' . $lampiran->nama_file);
                    $lampiranBase64[$lampiran->id] = null;
                }
            }
        }

        // Status labels
        $statusLabels = [
            'OPEN'       => 'Menunggu Penanganan',
            'ON_PROCESS' => 'Sedang Diproses',
            'WAITING'    => 'Dalam Proses Pengadaan',
            'DONE'       => 'Selesai',
            'CLOSED'     => 'Ditutup',
        ];

        $data = [
            'tiket' => $tiket,
            'statusLabels' => $statusLabels,
            'lampiranBase64' => $lampiranBase64,
            'generated_at' => now()->format('d/m/Y H:i:s'),
            'generated_by' => auth()->user()->name ?? 'System',
        ];

        // Generate PDF dengan opsi khusus
        $pdf = Pdf::loadView('help.report.tiket-detail', $data);
        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
            'chroot' => public_path(),
            'debugPng' => false, // Matikan debug PNG
            'debugKeepTemp' => false,
            'debugCss' => false,
            'dpi' => 96, // Turunkan DPI untuk mengurangi ukuran
            'enable_php' => false,
            'enable_javascript' => false,
            'enable_html5_parser' => true,
            'font_height_ratio' => 1.1,
        ]);
        
        // Download file
        $filename = 'tiket-' . str_replace('/', '-', $tiket->nomor_tiket) . '.pdf';
        return $pdf->download($filename);
    }
}