<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApartemenPeraturan extends Model
{
    use SoftDeletes;

    protected $table = 'tb_apartemen_peraturan';
    protected $fillable = [
        'apartemen_id',
        'isi_peraturan',
        'aktif'
    ];

    protected $casts = [
        'aktif' => 'boolean'
    ];

    protected $dates = ['deleted_at'];

    public function apartemen()
    {
        return $this->belongsTo(Apartemen::class, 'apartemen_id');
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}