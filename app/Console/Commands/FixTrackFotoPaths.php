<?php

namespace App\Console\Commands;

use App\Models\Foto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixTrackFotoPaths extends Command
{
    protected $signature = 'track-foto:fix-paths';
    protected $description = 'Memperbaiki path foto di database untuk TrackR';

    public function handle()
    {
        $fotos = Foto::all();
        $fixed = 0;
        $notFound = 0;
        
        $this->info('Memeriksa ' . $fotos->count() . ' foto...');
        $this->newLine();
        
        $progressBar = $this->output->createProgressBar($fotos->count());
        $progressBar->start();
        
        foreach ($fotos as $foto) {
            $oldPath = $foto->path;
            $newPath = $oldPath;
            $changed = false;
            
            // Cek apakah file ada di berbagai lokasi
            $locations = [
                'storage/app/public/' . $oldPath,
                'storage/app/public/fotos/' . $oldPath,
                'storage/app/public/track-r/' . $oldPath,
                'public/storage/' . $oldPath,
                'public/storage/fotos/' . $oldPath,
                'public/storage/track-r/' . $oldPath,
            ];
            
            $found = false;
            foreach ($locations as $location) {
                if (file_exists(base_path($location))) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $notFound++;
            }
            
            // Hapus 'public/' jika ada
            if (str_starts_with($newPath, 'public/')) {
                $newPath = substr($newPath, 7);
                $changed = true;
            }
            
            // Hapus 'storage/' jika ada
            if (str_starts_with($newPath, 'storage/')) {
                $newPath = substr($newPath, 8);
                $changed = true;
            }
            
            // Jika path hanya berisi nama file tanpa folder
            if (!str_contains($newPath, '/') && !str_contains($newPath, '\\')) {
                // Cek apakah file ada di folder fotos
                if (Storage::disk('public')->exists('fotos/' . $newPath)) {
                    $newPath = 'fotos/' . $newPath;
                    $changed = true;
                }
                // Cek apakah file ada di folder track-r
                elseif (Storage::disk('public')->exists('track-r/' . $newPath)) {
                    $newPath = 'track-r/' . $newPath;
                    $changed = true;
                }
            }
            
            if ($changed) {
                $foto->path = $newPath;
                $foto->save();
                $fixed++;
                $this->newLine();
                $this->line("  ✓ Fixed: $oldPath -> $newPath");
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        $this->info("✅ Selesai! Statistik:");
        $this->table(
            ['Total', 'Fixed', 'Not Found'],
            [[$fotos->count(), $fixed, $notFound]]
        );
        
        if ($notFound > 0) {
            $this->warn("⚠️  $notFound file tidak ditemukan di storage. Periksa lokasi file.");
        }
    }
}