<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DriverSwapLog extends Model
{
    protected $table = 'drms_driver_swap_logs';

    protected $fillable = [
        'request_id',
        'old_driver_id',
        'new_driver_id',
        'old_vehicle_id',
        'new_vehicle_id',
        'reason',
        'changed_by_user_id',
    ];

    public function request()
    {
        return $this->belongsTo(DriverRequest::class, 'request_id');
    }

    public function oldDriver()
    {
        return $this->belongsTo(Driver::class, 'old_driver_id');
    }

    public function newDriver()
    {
        return $this->belongsTo(Driver::class, 'new_driver_id');
    }

    public function oldVehicle()
    {
        return $this->belongsTo(Vehicle::class, 'old_vehicle_id');
    }

    public function newVehicle()
    {
        return $this->belongsTo(Vehicle::class, 'new_vehicle_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
