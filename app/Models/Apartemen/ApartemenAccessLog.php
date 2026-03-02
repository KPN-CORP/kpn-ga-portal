<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;

class ApartemenAccessLog extends Model
{
    protected $table = 'tb_apartemen_access_log';
    protected $fillable = [
        'unit_kode_id',
        'penghuni_id',
        'tipe',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'scan_time' => 'datetime'
    ];

    public $timestamps = false; // Karena pakai scan_time

    public function unitKode()
    {
        return $this->belongsTo(ApartemenUnitKode::class, 'unit_kode_id');
    }

    public function penghuni()
    {
        return $this->belongsTo(ApartemenPenghuni::class, 'penghuni_id');
    }
}