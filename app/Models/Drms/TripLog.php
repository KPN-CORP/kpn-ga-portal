<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TripLog extends Model
{
    protected $table = 'drms_trip_logs';
    
    protected $fillable = [
        'request_id',
        'odometer_start', 'odometer_finish',
        'fuel_type', 'fuel_volume', 'fuel_price_per_unit', 'fuel_cost',
        'photo_before', 'photo_after', 'photo_fuel_receipt',
        'is_submitted', 'submitted_at',
        'is_verified', 'verified_by', 'verified_at', 'verification_notes',
        'notes',
        'revision_note',
    ];

    protected $casts = [
        'odometer_start' => 'integer',
        'odometer_finish' => 'integer',
        'fuel_volume' => 'decimal:2',
        'fuel_price_per_unit' => 'decimal:2',
        'fuel_cost' => 'decimal:2',
        'is_submitted' => 'boolean',
        'is_verified' => 'boolean',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(DriverRequest::class, 'request_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Jarak tempuh (km)
     */
    public function getDistanceAttribute()
    {
        if ($this->odometer_start && $this->odometer_finish) {
            return $this->odometer_finish - $this->odometer_start;
        }
        return null;
    }

    /**
     * Efisiensi (km/liter atau km/kWh)
     */
    public function getEfficiencyAttribute()
    {
        if ($this->distance && $this->fuel_volume && $this->fuel_volume > 0) {
            return round($this->distance / $this->fuel_volume, 2);
        }
        return null;
    }
    public function needsRevision(): bool
    {
        return $this->is_submitted == 0 && $this->is_verified == 0 && !empty($this->verification_notes);
    }
}