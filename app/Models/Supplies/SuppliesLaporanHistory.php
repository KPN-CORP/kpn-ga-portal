<?php

namespace App\Models\Supplies;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SuppliesLaporanHistory extends Model
{
    protected $table = 'supplies_laporan_history';
    protected $fillable = [
        'id_user', 'jenis', 'id_barang',
        'tanggal_awal', 'tanggal_akhir'
    ];

    protected $casts = [
        'tanggal_awal' => 'date',
        'tanggal_akhir' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }


    public function barang()
    {
        return $this->belongsTo(SuppliesBarang::class, 'id_barang');
    }
}