<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApartemenAset extends Model
{
    use SoftDeletes;

    protected $table = 'tb_apartemen_aset';
    protected $fillable = ['nama_aset'];

    protected $dates = ['deleted_at'];

    public function units()
    {
        return $this->belongsToMany(ApartemenUnit::class, 'tb_apartemen_unit_aset', 'aset_id', 'unit_id')
                    ->withPivot('jumlah', 'kondisi', 'catatan');
    }
}