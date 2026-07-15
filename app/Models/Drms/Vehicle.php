<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\BisnisUnit;

class Vehicle extends Model
{
    protected $table = 'drms_vehicles';
    
    protected $fillable = [
        'type', 
        'plate_number', 
        'capacity', 
        'status', 
        'fuel_type',
        'business_unit_id',
        'gps_enabled'   // tambahan
    ];

    protected $casts = [
        'status'       => 'string',
        'gps_enabled'  => 'boolean', // cast ke boolean
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'business_unit_id', 'id_bisnis_unit');
    }

    public function requests()
    {
        return $this->hasMany(DriverRequest::class);
    }

    public function serviceSchedules()
    {
        return $this->hasMany(ServiceSchedule::class);
    }

    public function repairs()
    {
        return $this->hasMany(Repair::class);
    }

    public function fuelLogs()
    {
        return $this->hasMany(FuelLog::class);
    }

    public function document()
    {
        return $this->hasOne(VehicleDocument::class);
    }
}