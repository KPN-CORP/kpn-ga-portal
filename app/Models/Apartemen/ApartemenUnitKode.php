<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;

class ApartemenUnitKode extends Model
{
    protected $table = 'tb_apartemen_unit_kode';
    protected $fillable = [
        'unit_id',
        'kode_unik',
        'qr_path',
        'aktif'
    ];

    protected $casts = [
        'aktif' => 'boolean'
    ];

    public function unit()
    {
        return $this->belongsTo(ApartemenUnit::class, 'unit_id');
    }

    public function logs()
    {
        return $this->hasMany(ApartemenAccessLog::class, 'unit_kode_id');
    }

    // Generate kode unik
    public static function generateKodeUnik($unit_id)
    {
        $unit = ApartemenUnit::find($unit_id);
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $unit->apartemen->nama_apartemen), 0, 3));
        $nomor = str_pad($unit->nomor_unit, 5, '0', STR_PAD_LEFT);
        $random = strtoupper(substr(md5(uniqid()), 0, 4));
        
        return $prefix . $nomor . $random;
    }
}