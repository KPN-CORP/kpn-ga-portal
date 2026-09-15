<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FuelLog extends Model
{
    protected $table = 'drms_fuel_logs';

    protected $fillable = [
        'vehicle_id', 'driver_id', 'user_id', 'filling_date',
        'odometer_start', 'fuel_liters', 'fuel_price_per_liter',
        'receipt_file', 'is_verified', 'verified_by', 'verified_at', 'notes'
    ];

    protected $casts = [
        'filling_date' => 'date',
        'odometer_start' => 'integer',
        'fuel_liters' => 'decimal:2',
        'fuel_price_per_liter' => 'decimal:2',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Konsumsi dihitung dari selisih odometer antar entri
     * Tidak dihitung per entri karena tidak punya odometer_end
     */
}