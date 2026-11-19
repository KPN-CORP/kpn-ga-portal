<?php
namespace App\Models\Work;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class WorkReportLog extends Model
{
    protected $table = 'work_report_logs';
    public $timestamps = false;

    protected $fillable = [
        'work_report_id', 'user_id', 'action', 'old_data', 'new_data'
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function workReport()
    {
        return $this->belongsTo(WorkReport::class, 'work_report_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}