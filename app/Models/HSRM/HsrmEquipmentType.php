<?php

namespace App\Models\HSRM;

use Illuminate\Database\Eloquent\Model;

class HsrmEquipmentType extends Model
{
    protected $table = 'hsrm_equipment_types';
    protected $fillable = ['name', 'description'];

    public function equipments()
    {
        return $this->hasMany(HsrmEquipment::class, 'equipment_type_id');
    }
}