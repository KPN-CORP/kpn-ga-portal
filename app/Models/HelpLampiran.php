<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpLampiran extends Model
{
    const TYPE_INITIAL = 'INITIAL';
    const TYPE_FOLLOW_UP = 'FOLLOW_UP';
    const TYPE_COMPLETION = 'COMPLETION';
    
    protected $table = 'db_help_lampiran';
    
    protected $fillable = [
        'tiket_id',
        'pengguna_id',
        'path_file',
        'nama_file',
        'tipe_file',
        'ukuran_file',
        'tipe'
    ];
    
    /**
     * Relasi ke tiket
     */
    public function tiket(): BelongsTo
    {
        return $this->belongsTo(HelpTiket::class, 'tiket_id');
    }
    
    /**
     * Relasi ke pengguna (pelanggan)
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pengguna_id', 'id_pelanggan');
    }
    
    /**
     * Get tipe label
     */
    public function getTypeLabelAttribute(): string
    {
        $labels = [
            self::TYPE_INITIAL => 'Awal',
            self::TYPE_FOLLOW_UP => 'Diskusi',
            self::TYPE_COMPLETION => 'Hasil'
        ];
        
        return $labels[$this->tipe] ?? $this->tipe;
    }
    
    /**
     * Get badge color
     */
    public function getTypeBadgeColorAttribute(): string
    {
        $colors = [
            self::TYPE_INITIAL => 'bg-blue-100 text-blue-800',
            self::TYPE_FOLLOW_UP => 'bg-gray-100 text-gray-800',
            self::TYPE_COMPLETION => 'bg-green-100 text-green-800'
        ];
        
        return $colors[$this->tipe] ?? 'bg-gray-100 text-gray-800';
    }
    
    /**
     * Format ukuran file
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->ukuran_file;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 1) . ' ' . $units[$pow];
    }
    
    /**
     * Cek apakah file adalah gambar
     */
    public function getIsImageAttribute(): bool
    {
        return str_contains($this->tipe_file, 'image');
    }
    
    /**
     * Get icon class berdasarkan tipe file
     */
    public function getIconClassAttribute(): string
    {
        if ($this->is_image) {
            return 'fas fa-image text-blue-500';
        }
        
        if (str_contains($this->tipe_file, 'pdf')) {
            return 'fas fa-file-pdf text-red-500';
        }
        
        if (str_contains($this->tipe_file, 'word') || str_contains($this->tipe_file, 'document')) {
            return 'fas fa-file-word text-blue-500';
        }
        
        if (str_contains($this->tipe_file, 'excel') || str_contains($this->tipe_file, 'sheet')) {
            return 'fas fa-file-excel text-green-600';
        }
        
        return 'fas fa-file text-gray-500';
    }
}