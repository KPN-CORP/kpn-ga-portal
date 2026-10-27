<?php

namespace App\Models\Task_M;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskUnit extends Model
{
    use HasFactory;

    public const STATUS_LABELS = [
        'pending'   => 'Proses',
        'done'      => 'Selesai',
        'cancelled' => 'Dibatalkan',
    ];

    public const STATUS_CLASSES = [
        'pending'   => 'bg-yellow-100 text-yellow-700',
        'done'      => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-red-100 text-red-700',
    ];

    protected $table = 'task_units';
    protected $fillable = ['task_monitor_id', 'description', 'status', 'completed_at'];

    protected $casts = [
        'status'       => 'string',
        'completed_at' => 'datetime',
    ];

    public function taskMonitor()
    {
        return $this->belongsTo(TaskMonitor::class);
    }

    public function isFinal()
    {
        return in_array($this->status, ['done', 'cancelled']);
    }

    /** Label status seragam di semua halaman (Proses / Selesai / Dibatalkan) */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusClassAttribute(): string
    {
        return self::STATUS_CLASSES[$this->status] ?? 'bg-gray-100 text-gray-600';
    }

    /** Waktu selesai / dibatalkan (null bila masih proses) */
    public function getFinalAtAttribute()
    {
        if (!$this->isFinal()) {
            return null;
        }
        return $this->completed_at ?? $this->updated_at;
    }
}
