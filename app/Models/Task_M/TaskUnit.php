<?php

namespace App\Models\Task_M;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskUnit extends Model
{
    use HasFactory;

    protected $table = 'task_units';
    protected $fillable = ['task_monitor_id', 'description', 'status'];

    protected $casts = [
        'status' => 'string',
    ];

    public function taskMonitor()
    {
        return $this->belongsTo(TaskMonitor::class);
    }

    public function isFinal()
    {
        return in_array($this->status, ['done', 'cancelled']);
    }
}