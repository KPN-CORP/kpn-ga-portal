<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Apartemen extends Model
{
    use SoftDeletes;

    protected $table = 'tb_apartemen';
    protected $fillable = [
        'nama_apartemen', 
        'alamat', 
        'penanggung_jawab',
        'kontak_darurat',
        'telepon',
        'email'
    ];

    // Relasi ke unit
    public function units()
    {
        return $this->hasMany(ApartemenUnit::class, 'apartemen_id')
            ->whereNull('deleted_at');
    }

    // Scopes untuk filter
    public function scopeWithUnitCounts($query)
    {
        return $query->withCount([
            'units as units_count',
            'units as units_ready' => function ($q) {
                $q->where('status', 'READY');
            },
            'units as units_terisi' => function ($q) {
                $q->where('status', 'TERISI');
            },
            'units as units_maintenance' => function ($q) {
                $q->where('status', 'MAINTENANCE');
            }
        ]);
    }
}