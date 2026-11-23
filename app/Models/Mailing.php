<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mailing extends Model
{
    protected $table = 'tb_mailing';
    protected $primaryKey = 'id_mailing';
    
    protected $fillable = [
        'mailing_resi',
        'mailing_pengirim',
        'mailing_penerima',
        'mailing_penerima_distribusi',
        'mailing_penerima_id',
        'mailing_expedisi',
        'mailing_lantai',
        'mailing_tanggal_input',
        'mailing_input_by',
        'mailing_tanggal_ob47',
        'mailing_ob47_by',
        'mailing_petugas',
        'mailing_tanggal_distribusi',
        'mailing_distribusi_by',
        'mailing_tanggal_selesai',
        'mailing_selesai_by',
        'mailing_foto',
        'mailing_foto_diterima',
        'mailing_status',
        'mailing_prioritas',
        'mailing_keterangan',
        'id_ekspedisi'
    ];
    
    protected $casts = [
        'mailing_tanggal_input' => 'datetime',
        'mailing_tanggal_ob47' => 'datetime',
        'mailing_tanggal_distribusi' => 'datetime',
        'mailing_tanggal_selesai' => 'datetime',
    ];
    
    // Relasi ke ekspedisi
    public function ekspedisi()
    {
        return $this->belongsTo(Ekspedisi::class, 'id_ekspedisi', 'id_ekspedisi');
    }
    
    // Relasi ke pelanggan
    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'mailing_penerima_id', 'id_pelanggan');
    }
    
    // Accessor untuk foto URL
    public function getFotoUrlAttribute()
    {
        if (!$this->mailing_foto) {
            return asset('images/no-image.jpg');
        }
        
        return route('mailing.view-foto', $this->id_mailing);
    }
}