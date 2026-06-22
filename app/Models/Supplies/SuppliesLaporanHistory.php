<?php

namespace App\Models\Supplies;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BisnisUnit;

class SuppliesLaporanHistory extends Model
{
    protected $table = 'supplies_laporan_history';
    protected $fillable = [
        'id_user', 'jenis', 'id_bisnis_unit', 'id_barang',
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

    public function bisnisUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'id_bisnis_unit', 'id_bisnis_unit');
    }

    public function barang()
    {
        return $this->belongsTo(SuppliesBarang::class, 'id_barang');
    }
}