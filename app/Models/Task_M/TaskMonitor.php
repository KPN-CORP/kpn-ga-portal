<?php

namespace App\Models\Task_M;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TaskMonitor extends Model
{
    use HasFactory;

    protected $table = 'task_monitors';
    protected $fillable = ['user_id', 'title', 'start_date', 'end_date'];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function units()
    {
        return $this->hasMany(TaskUnit::class, 'task_monitor_id');
    }

    public function doneUnitsCount()
    {
        return $this->units()->where('status', 'done')->count();
    }

    public function activeUnitsCount()
    {
        return $this->units()->where('status', '!=', 'cancelled')->count();
    }

    public function progressPercentage()
    {
        $active = $this->activeUnitsCount();
        if ($active === 0) return 0;
        return floor(($this->doneUnitsCount() / $active) * 100);
    }

    /**
     * Statistik untuk user (dengan filter tahun)
     * Tahun filter berdasarkan tahun start_date
     */
    public static function getUserStats($userId, $year = null)
    {
        $query = self::where('user_id', $userId);
        if ($year) {
            $query->whereYear('start_date', $year);
        }
        $projects = $query->with('units')->get();

        $totalProjects = $projects->count();
        $totalPending = $projects->sum(fn($p) => $p->units->where('status', 'pending')->count());
        $totalDone = $projects->sum(fn($p) => $p->units->where('status', 'done')->count());

        return [
            'total_projects' => $totalProjects,
            'total_pending'  => $totalPending,
            'total_done'     => $totalDone,
        ];
    }

    /**
     * Mendapatkan daftar tahun unik dari start_date untuk user tertentu
     */
    public static function getAvailableYears($userId)
    {
        return self::where('user_id', $userId)
            ->selectRaw('DISTINCT YEAR(start_date) as year')
            ->orderBy('year', 'desc')
            ->pluck('year');
    }
}