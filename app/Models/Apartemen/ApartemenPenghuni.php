<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;

class ApartemenPenghuni extends Model
{
    protected $table = 'tb_apartemen_penghuni';
    protected $fillable = [
        'assign_id',
        'nama',
        'id_karyawan',
        'no_hp',
        'unit_kerja',
        'gol',
        'tanggal_mulai',
        'tanggal_selesai',
        'status'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date'
    ];

    public function assign()
    {
        return $this->belongsTo(ApartemenAssign::class, 'assign_id');
    }

    public function unit()
    {
        return $this->hasOneThrough(ApartemenUnit::class, ApartemenAssign::class, 'id', 'id', 'assign_id', 'unit_id');
    }

    public function apartemen()
    {
        return $this->hasOneThrough(
            Apartemen::class,
            ApartemenUnit::class,
            'id',
            'id',
            'assign.unit_id',
            'apartemen_id'
        );
    }

    public function getStatusTextAttribute()
    {
        $statuses = [
            'AKTIF' => 'Aktif',
            'SELESAI' => 'Selesai'
        ];

        return $statuses[$this->status] ?? $this->status;
    }
}