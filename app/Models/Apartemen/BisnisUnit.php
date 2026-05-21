<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;

class BisnisUnit extends Model
{
    protected $table = 'tb_bisnis_unit';
    protected $primaryKey = 'id_bisnis_unit';
    public $timestamps = false;

    protected $fillable = ['nama_bisnis_unit'];

    public function units()
    {
        return $this->hasMany(ApartemenUnit::class, 'bisnis_unit_id', 'id_bisnis_unit');
    }
}