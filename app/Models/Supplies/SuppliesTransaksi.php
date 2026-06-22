<?php

namespace App\Models\Supplies;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BisnisUnit;

class SuppliesTransaksi extends Model
{
    protected $table = 'supplies_transaksi';
    public $timestamps = false;
    protected $fillable = [
        'jenis', 'id_barang', 'jumlah', 'id_bisnis_unit', 'id_permintaan',
        'no_ref', 'keterangan', 'id_user', 'tanggal'
    ];
    protected $casts = ['tanggal' => 'datetime'];

    public function barang()
    {
        return $this->belongsTo(SuppliesBarang::class, 'id_barang');
    }

    public function bisnisUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'id_bisnis_unit', 'id_bisnis_unit');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function permintaan()
    {
        return $this->belongsTo(SuppliesPermintaan::class, 'id_permintaan');
    }
}