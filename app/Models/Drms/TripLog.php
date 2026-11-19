<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TripLog extends Model
{
    protected $table = 'drms_trip_logs';
    protected $dates = ['revision_requested_at'];
    
    protected $fillable = [
        'request_id',
        'odometer_start', 'odometer_finish',
        'photo_before', 'photo_after',
        'is_submitted', 'submitted_at',
        'is_verified', 'verified_by', 'verified_at', 'verification_notes',
        'notes',
        'revision_note',
    ];

    protected $casts = [
        'odometer_start' => 'integer',
        'odometer_finish' => 'integer',
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

    public function getDistanceAttribute()
    {
        if ($this->odometer_start && $this->odometer_finish) {
            return $this->odometer_finish - $this->odometer_start;
        }
        return null;
    }

    public function isRevisionExpired(): bool
    {
        if (!$this->revision_requested_at) {
            return false;
        }
        return \Carbon\Carbon::now()->diffInDays($this->revision_requested_at) >= 7;
    }

    public function needsRevision(): bool
    {
        return $this->is_submitted == 0 && $this->is_verified == 0 && !empty($this->verification_notes);
    }
}