<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaKerja extends Model
{
    protected $table = 'stock_ctl_area_kerja';
    protected $primaryKey = 'id_area_kerja';
    public $timestamps = false;

    protected $fillable = ['nama_area', 'id_bisnis_unit'];

    public function businessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'id_bisnis_unit', 'id_bisnis_unit');
    }

    // Relasi ke user melalui hsrm_user_roles (bukan hsrm_pic_areas)
    public function users()
    {
        return $this->belongsToMany(User::class, 'hsrm_user_roles', 'area_id', 'user_id');
    }

    // Alias untuk PIC
    public function pics()
    {
        return $this->users();
    }
}