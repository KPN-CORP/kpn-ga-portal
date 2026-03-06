<?php
// app/Models/Apartemen/ApartemenAccessCode.php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;

class ApartemenAccessCode extends Model
{
    protected $table = 'tb_apartemen_access_codes';
    
    protected $fillable = [
        'kode_akses',
        'nama_akses',
        'tipe',
        'is_active',
        'max_uses',
        'used_count',
        'expired_at'
        // HANYA kolom yang ADA di tabel
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'expired_at' => 'datetime',
        'max_uses' => 'integer',
        'used_count' => 'integer'
    ];
    
    public static function generateKodeAkses($length = 6)
    {
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $kode = '';
        
        for ($i = 0; $i < $length; $i++) {
            $kode .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        // Pastikan unik
        while (self::where('kode_akses', $kode)->exists()) {
            $kode = '';
            for ($i = 0; $i < $length; $i++) {
                $kode .= $characters[random_int(0, strlen($characters) - 1)];
            }
        }
        
        return $kode;
    }
    
    public function isValid($ip = null)
    {
        if (!$this->is_active) {
            return false;
        }
        
        if ($this->expired_at && $this->expired_at->isPast()) {
            return false;
        }
        
        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }
        
        return true;
    }
    
    public function incrementUsed()
    {
        return $this->increment('used_count');
    }
}