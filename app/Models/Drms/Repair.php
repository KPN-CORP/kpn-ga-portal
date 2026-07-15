<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Repair extends Model
{
    protected $table = 'drms_repairs';
    protected $fillable = [
        'vehicle_id', 'reported_by', 'report_date', 'complaint',
        'diagnosis', 'parts_replaced', 'labor_cost', 'parts_cost',
        'status', 'completed_at', 'notes', 'created_by'
    ];

    protected $casts = [
        'report_date' => 'date',
        'completed_at' => 'datetime',
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}