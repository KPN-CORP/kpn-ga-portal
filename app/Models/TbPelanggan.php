<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TbPelanggan extends Model
{
    protected $table = 'tb_pelanggan';
    protected $primaryKey = 'id_pelanggan';
    public $timestamps = true;
    
    protected $fillable = [
        'id_login', 'lantai_aktif', 'nama_pelanggan', 'username_pelanggan',
        'password', 'bisnis_unit', 'departemen', 'pic', 'no_hp_pelanggan',
        'email_pelanggan', 'gambar', 'role_akses'
    ];
    
    protected $hidden = ['password'];
    
    // Relasi ke User Laravel
    public function user()
    {
        return $this->belongsTo(User::class, 'id_login', 'id');
    }
    
    // Relasi ke transaksi (sebagai pengirim)
    public function transaksi()
    {
        return $this->hasMany(TransaksiMessenger::class, 'pengirim', 'id_pelanggan');
    }
}