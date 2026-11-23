<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ekspedisi extends Model
{
    protected $table = 'tb_ekspedisi';
    protected $primaryKey = 'id_ekspedisi';
    public $timestamps = false;
    
    protected $fillable = [
        'nama_ekspedisi',
        'deskripsi',
        'tanggal_dibuat'
    ];
    
    // Relasi ke mailing
    public function mailings()
    {
        return $this->hasMany(Mailing::class, 'id_ekspedisi', 'id_ekspedisi');
    }
}