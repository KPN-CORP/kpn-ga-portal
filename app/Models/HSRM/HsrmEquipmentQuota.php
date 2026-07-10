<?php

namespace App\Models\HSRM;

use Illuminate\Database\Eloquent\Model;
use App\Models\AreaKerja;

class HsrmEquipmentQuota extends Model
{
    protected $table = 'hsrm_equipment_quotas';

    protected $fillable = [
        'area_id',
        'equipment_type_id',
        'quota',
        'budget',
        'application_type',
    ];

    public function area()
    {
        return $this->belongsTo(AreaKerja::class, 'area_id', 'id_area_kerja');
    }

    public function equipmentType()
    {
        return $this->belongsTo(HsrmEquipmentType::class);
    }

    public function equipments()
    {
        return $this->hasMany(HsrmEquipment::class, 'area_id', 'area_id')
                    ->where('equipment_type_id', $this->equipment_type_id);
    }

    public function getActiveCountAttribute()
    {
        return $this->equipments()
                    ->where('status_verif', 'verified')
                    ->where('expired_date', '>', now())
                    ->sum('total_items');
    }

    public function getExpiredCountAttribute()
    {
        return $this->equipments()
                    ->where('expired_date', '<=', now())
                    ->sum('total_items');
    }
}