<?php

namespace App\Models\Supplies;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BisnisUnit;

class SuppliesPermintaan extends Model
{
    protected $table = 'supplies_permintaan';
    protected $fillable = [
        'id_user_pemohon', 'id_barang', 'id_bisnis_unit', 'jumlah',
        'keterangan', 'status', 'approved_by', 'approved_at', 'alasan_tolak'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function pemohon()
    {
        return $this->belongsTo(User::class, 'id_user_pemohon');
    }

    public function barang()
    {
        return $this->belongsTo(SuppliesBarang::class, 'id_barang');
    }

    public function bisnisUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'id_bisnis_unit', 'id_bisnis_unit');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}