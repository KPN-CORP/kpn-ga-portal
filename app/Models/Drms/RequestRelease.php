<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;

/**
 * Slot driver/mobil yang dilepas sementara dari request multi-hari (lihat SlotService).
 */
class RequestRelease extends Model
{
    protected $table = 'drms_request_releases';

    protected $fillable = [
        'request_id', 'release_date', 'free_from', 'free_until',
        'vehicle_released', 'at_home_city',
        'released_by', 'released_role', 'note',
        'cancelled_at', 'cancelled_by',
    ];

    protected $casts = [
        'release_date'     => 'date',
        'free_from'        => 'datetime',
        'free_until'       => 'datetime',
        'vehicle_released' => 'boolean',
        'at_home_city'     => 'boolean',
        'cancelled_at'     => 'datetime',
    ];

    public function request()
    {
        return $this->belongsTo(DriverRequest::class, 'request_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('cancelled_at');
    }
}
