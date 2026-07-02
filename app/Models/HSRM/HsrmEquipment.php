<?php

namespace App\Models\HSRM;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BisnisUnit;
use App\Models\AreaKerja;

class HsrmEquipment extends Model
{
    protected $table = 'hsrm_equipments';

    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'business_unit_id',
        'area_id',
        'pic_user_id',
        'name',
        'equipment_type_id',
        'capacity',
        'location',
        'expired_date',
        'status_verif',
        'status_kepemilikan',
        'rekomendasi',
        'photo_path',
        'old_attachments',
        'notes',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expired_date' => 'date',
        'approved_at' => 'datetime',
        'status_kepemilikan' => 'boolean',
        'rekomendasi' => 'boolean',
        'old_attachments' => 'array',
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'business_unit_id', 'id_bisnis_unit');
    }

    public function area()
    {
        return $this->belongsTo(AreaKerja::class, 'area_id', 'id_area_kerja');
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function equipmentType()
    {
        return $this->belongsTo(HsrmEquipmentType::class);
    }
}