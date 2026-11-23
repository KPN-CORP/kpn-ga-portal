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

    /**
     * Koleksi unit yang sudah dimuat (eager load). Bila belum dimuat, dimuat sekali saja,
     * sehingga hitungan di bawah tidak menjalankan query baru setiap dipanggil.
     */
    protected function unitsCollection()
    {
        $this->loadMissing('units:id,task_monitor_id,status');
        return $this->units;
    }

    public function doneUnitsCount()
    {
        return $this->unitsCollection()->where('status', 'done')->count();
    }

    public function pendingUnitsCount()
    {
        return $this->unitsCollection()->where('status', 'pending')->count();
    }

    public function cancelledUnitsCount()
    {
        return $this->unitsCollection()->where('status', 'cancelled')->count();
    }

    public function activeUnitsCount()
    {
        return $this->unitsCollection()->where('status', '!=', 'cancelled')->count();
    }

    public function progressPercentage()
    {
        $active = $this->activeUnitsCount();
        if ($active === 0) return 0;
        return floor(($this->doneUnitsCount() / $active) * 100);
    }

    /**
     * Statistik untuk user (dengan filter tahun) - hanya 2 query, tanpa memuat semua unit.
     * Tahun filter berdasarkan tahun start_date.
     */
    public static function getUserStats($userId, $year = null)
    {
        $projects = self::where('user_id', $userId);
        if ($year) {
            $projects->whereYear('start_date', $year);
        }

        $totalProjects = (clone $projects)->count();

        $counts = TaskUnit::whereIn('task_monitor_id', (clone $projects)->select('id'))
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'total_projects' => $totalProjects,
            'total_pending'  => (int) ($counts['pending'] ?? 0),
            'total_done'     => (int) ($counts['done'] ?? 0),
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
