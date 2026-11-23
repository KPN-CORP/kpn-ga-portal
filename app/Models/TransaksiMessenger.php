<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiMessenger extends Model
{
    protected $table = 'tb_transaksi';
    protected $primaryKey = 'no_transaksi';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;
    
    protected $fillable = [
        'no_transaksi',
        'alamat_asal',
        'alamat_tujuan',
        'penerima',
        'pengirim', // Ini adalah id_pelanggan
        'nama_barang',
        'deskripsi',
        'no_hp_penerima',
        'foto_barang',
        'waktu',
        'status',
        'gambar_awal',
        'gambar_akhir',
        'kurir',
        'penilaian',
        'note_penerima',
        'note_penerima',
        'email_sent'
    ];
    
    // Relasi ke tb_pelanggan (pengirim adalah id_pelanggan)
    public function pelanggan()
    {
        return $this->belongsTo(TbPelanggan::class, 'pengirim', 'id_pelanggan');
    }
    
    // Relasi ke user melalui pelanggan
    public function user()
    {
        return $this->hasOneThrough(
            User::class,
            TbPelanggan::class,
            'id_pelanggan', // Foreign key on tb_pelanggan
            'id', // Foreign key on users
            'pengirim', // Local key on tb_transaksi
            'id_login' // Local key on tb_pelanggan
        );
    }
}