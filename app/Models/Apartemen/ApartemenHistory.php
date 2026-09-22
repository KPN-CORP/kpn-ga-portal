<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApartemenHistory extends Model
{
    use SoftDeletes;

    protected $table = 'tb_apartemen_history';
    protected $fillable = [
        'nama',
        'id_karyawan',
        'no_hp', 
        'unit_kerja',
        'gol',
        'apartemen',
        'unit',
        'periode',
        'status_selesai',
        'catatan',
        'bisnis_unit'
    ];

    // Kolom created_at sudah ada di tabel, jadi timestamps aktif
    public $timestamps = true;
    
    // Tapi tabel tidak punya updated_at, jadi kita set manual
    const UPDATED_AT = null;

    protected $casts = [
        'created_at' => 'datetime'
    ];
}