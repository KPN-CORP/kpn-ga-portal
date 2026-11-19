<?php

namespace App\Models\Work;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class WorkReport extends Model
{
    protected $table = 'work_reports';

    protected $fillable = [
        'category_id', 'photo_before', 'photo_after', 'floor', 'location',
        'report_date', 'start_time', 'end_time', 'description', 'created_by'
    ];

    protected $casts = [
        'report_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function category()
    {
        return $this->belongsTo(WorkReportCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs()
    {
        return $this->hasMany(WorkReportLog::class, 'work_report_id');
    }

    public function isEditable()
    {
        return $this->created_at->diffInHours(now()) < 12;
    }

    public function canBeModifiedBy($user)
    {
        if (!$this->isEditable()) return false;
        if ($user->isWorkAdmin()) return true;
        return $this->created_by == $user->id;
    }
}