<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class ServiceSchedule extends Model
{
    protected $table = 'drms_service_schedules';
    protected $fillable = [
        'vehicle_id', 'service_date', 'odometer_at_service', 'service_type',
        'workshop_name', 'cost', 'invoice_file', 'next_service_odometer',
        'next_service_date', 'notes', 'created_by',
        // Alur pengajuan: open -> progress -> done (atau rejected)
        'status', 'approved_by', 'approved_at', 'completed_by', 'completed_at', 'rejection_reason',
    ];

    protected $casts = [
        'service_date' => 'date',
        'next_service_date' => 'date',
        'cost' => 'decimal:2',
        'service_type' => 'array',
        'approved_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
