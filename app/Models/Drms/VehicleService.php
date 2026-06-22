<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BisnisUnit;

class VehicleService extends Model
{
    protected $table = 'drms_vehicle_services';
    
    protected $fillable = [
        'vehicle_id', 'business_unit_id',
        'service_date', 'odometer_at_service',
        'cost', 'description', 'photo_evidence',
        'created_by',
    ];

    protected $casts = [
        'service_date' => 'date',
        'odometer_at_service' => 'integer',
        'cost' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'business_unit_id', 'id_bisnis_unit');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}