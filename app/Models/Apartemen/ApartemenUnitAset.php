<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;

class ApartemenUnitAset extends Model
{
    protected $table = 'tb_apartemen_unit_aset';
    protected $fillable = [
        'unit_id',
        'aset_id',
        'jumlah',
        'kondisi',
        'catatan'
    ];

    protected $casts = [
        'jumlah' => 'integer'
    ];

    public function unit()
    {
        return $this->belongsTo(ApartemenUnit::class, 'unit_id');
    }

    public function aset()
    {
        return $this->belongsTo(ApartemenAset::class, 'aset_id');
    }

    public function getKondisiTextAttribute()
    {
        $kondisi = [
            'BAIK' => 'Baik',
            'RUSAK' => 'Rusak',
            'PERBAIKAN' => 'Perbaikan'
        ];

        return $kondisi[$this->kondisi] ?? $this->kondisi;
    }
}