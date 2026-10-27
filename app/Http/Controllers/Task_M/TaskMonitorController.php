<?php

namespace App\Http\Controllers\Task_M;

use App\Http\Controllers\Controller;
use App\Models\Task_M\TaskMonitor;
use App\Models\Task_M\TaskUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class TaskMonitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // ---------------------------------------------------------------
    // Helper otorisasi (sebelumnya diulang di setiap method)
    // ---------------------------------------------------------------
    private function isSuper(): bool
    {
        return Auth::user()->isTaskMonitorSuperadmin();
    }

    /** Hanya pemilik data atau superadmin yang boleh lanjut. */
    private function authorizeOwner($ownerId, string $message = 'Tidak memiliki akses ke data ini.'): void
    {
        if ((int) $ownerId !== (int) Auth::id() && !$this->isSuper()) {
            abort(403, $message);
        }
    }

    private function findProject($id): TaskMonitor
    {
        $project = TaskMonitor::findOrFail($id);
        $this->authorizeOwner($project->user_id);
        return $project;
    }

    /** Tujuan redirect setelah store / destroy: kembali ke daftar milik pemilik project. */
    private function redirectToOwnerList($ownerId)
    {
        if ($this->isSuper()) {
            return redirect()->route('task-m.user.projects', $ownerId);
        }
        return redirect()->route('task-m.index');
    }

    /** Aturan validasi project. Tanggal boleh kosong saat membuat project baru. */
    private function projectRules(Request $request, bool $datesRequired): array
    {
        if ($datesRequired) {
            return [
                'title'      => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date'   => 'required|date|after_or_equal:start_date',
            ];
        }

        return [
            'title'      => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'end_date'   => [
                'nullable',
                'date',
                Rule::when($request->filled('start_date'), ['after_or_equal:start_date']),
            ],
            'user_id'    => 'nullable|integer',
        ];
    }

    /** Data untuk halaman daftar project (dipakai index & userProjects). */
    private function projectListData($userId, $year): array
    {
        $query = TaskMonitor::where('user_id', $userId)
            ->with('units:id,task_monitor_id,status');
        if ($year) {
            $query->whereYear('start_date', $year);
        }

        return [
            'projects'       => $query->orderBy('created_at', 'desc')->get(),
            'stats'          => TaskMonitor::getUserStats($userId, $year),
            'availableYears' => TaskMonitor::getAvailableYears($userId),
            'year'           => $year,
        ];
    }

    // ---------------------------------------------------------------
    // Halaman utama: superadmin -> daftar user, user biasa -> project sendiri
    // ---------------------------------------------------------------
    public function index(Request $request)
    {
        if ($this->isSuper()) {
            $users = User::whereHas('taskMonitors')->orderBy('name')->get();
            $ids   = $users->pluck('id');

            $stats = [];
            foreach ($users as $u) {
                $stats[$u->id] = TaskMonitor::getUserStats($u->id);
            }

            $weekStart = now()->startOfWeek(Carbon::MONDAY);

            // Jumlah selesai minggu ini per user
            $doneThisWeek = TaskUnit::join('task_monitors', 'task_monitors.id', '=', 'task_units.task_monitor_id')
                ->whereIn('task_monitors.user_id', $ids)
                ->where('task_units.status', 'done')
                ->where('task_units.completed_at', '>=', $weekStart)
                ->selectRaw('task_monitors.user_id as uid, COUNT(*) as total')
                ->groupBy('task_monitors.user_id')
                ->pluck('total', 'uid');

            // Tanggal item proses tertua per user
            $oldestPending = TaskUnit::join('task_monitors', 'task_monitors.id', '=', 'task_units.task_monitor_id')
                ->whereIn('task_monitors.user_id', $ids)
                ->where('task_units.status', 'pending')
                ->selectRaw('task_monitors.user_id as uid, MIN(task_units.created_at) as oldest')
                ->groupBy('task_monitors.user_id')
                ->pluck('oldest', 'uid');

            return view('task-m.users', compact('users', 'stats', 'doneThisWeek', 'oldestPending'));
        }

        return view('task-m.index', $this->projectListData(Auth::id(), $request->get('year')));
    }

    // Menampilkan project milik user tertentu (hanya superadmin)
    public function userProjects(Request $request, $userId)
    {
        if (!$this->isSuper()) {
            abort(403, 'Hanya superadmin yang bisa mengakses halaman ini.');
        }

        $user = User::findOrFail($userId);

        return view('task-m.index', array_merge(
            $this->projectListData($user->id, $request->get('year')),
            ['user' => $user]
        ));
    }

    // Store project baru (superadmin boleh membuatkan untuk user yang sedang dilihat)
    public function store(Request $request)
    {
        $request->validate($this->projectRules($request, false));

        $ownerId = (int) ($request->input('user_id') ?: Auth::id());
        $this->authorizeOwner($ownerId);
        if ($ownerId !== (int) Auth::id()) {
            User::findOrFail($ownerId);
        }

        // Tanggal kosong = project tanpa deadline: pakai hari ini (end = start)
        $start = $request->start_date ?: ($request->end_date ?: now()->toDateString());
        $end   = $request->end_date ?: $start;

        TaskMonitor::create([
            'user_id'    => $ownerId,
            'title'      => $request->title,
            'start_date' => $start,
            'end_date'   => $end,
        ]);

        return $this->redirectToOwnerList($ownerId)->with('success', 'Project berhasil dibuat.');
    }

    // Detail project
    public function show($id)
    {
        $project = TaskMonitor::with([
            'user:id,name',
            // Item yang masih proses tampil paling atas
            'units' => fn ($q) => $q
                ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'done' THEN 1 ELSE 2 END")
                ->orderBy('id'),
        ])->findOrFail($id);

        $this->authorizeOwner($project->user_id, 'Tidak memiliki akses ke project ini.');

        return view('task-m.show', compact('project'));
    }

    // Update project (judul & tanggal)
    public function update(Request $request, $id)
    {
        $project = $this->findProject($id);

        $request->validate($this->projectRules($request, true));

        $project->update($request->only(['title', 'start_date', 'end_date']));
        return redirect()->route('task-m.show', $project->id)->with('success', 'Project diperbarui.');
    }

    // Hapus project
    public function destroy($id)
    {
        $project = $this->findProject($id);
        $ownerId = $project->user_id;
        $project->delete();

        return $this->redirectToOwnerList($ownerId)->with('success', 'Project dihapus.');
    }

    // ---- Unit management ----
    public function addUnit(Request $request, $projectId)
    {
        $project = $this->findProject($projectId);

        $request->validate(['description' => 'required|string|max:500']);
        TaskUnit::create([
            'task_monitor_id' => $project->id,
            'description'     => $request->description,
            'status'          => 'pending',
        ]);
        return redirect()->route('task-m.show', $project->id)->with('success', 'Progres ditambahkan.');
    }

    public function updateUnitStatus(Request $request, $projectId, $unitId)
    {
        $project = $this->findProject($projectId);
        $unit    = TaskUnit::where('task_monitor_id', $project->id)->findOrFail($unitId);

        $back = redirect()->route('task-m.show', $project->id);

        if ($unit->isFinal()) {
            return $back->with('error', 'Unit sudah final, tidak bisa diubah statusnya.');
        }

        $action = $request->action;
        if (!in_array($action, ['done', 'cancel'], true)) {
            return $back->with('error', 'Aksi tidak valid.');
        }

        // Update bersyarat: hanya berhasil bila unit masih 'pending'.
        // Mencegah tap ganda / dua tab saling menimpa status.
        $changed = TaskUnit::where('id', $unit->id)
            ->where('status', 'pending')
            ->update([
                'status'       => $action === 'done' ? 'done' : 'cancelled',
                // Catat kapan unit selesai (dasar untuk laporan mingguan)
                'completed_at' => $action === 'done' ? now() : null,
            ]);

        if (!$changed) {
            return $back->with('error', 'Unit sudah final, tidak bisa diubah statusnya.');
        }

        return $back->with('success', 'Status progres diperbarui.');
    }

    public function updateUnitDescription(Request $request, $projectId, $unitId)
    {
        $project = $this->findProject($projectId);
        $unit    = TaskUnit::where('task_monitor_id', $project->id)->findOrFail($unitId);

        // Unit final dikunci agar laporan mingguan tidak berubah diam-diam
        if ($unit->isFinal()) {
            return redirect()->route('task-m.show', $project->id)
                ->with('error', 'Unit sudah final, deskripsi tidak bisa diubah.');
        }

        $request->validate(['description' => 'required|string|max:500']);
        $unit->description = $request->description;
        $unit->save();

        return redirect()->route('task-m.show', $project->id)->with('success', 'Deskripsi progres diperbarui.');
    }

    public function deleteUnit($projectId, $unitId)
    {
        $project = $this->findProject($projectId);

        $unit = TaskUnit::where('task_monitor_id', $project->id)->findOrFail($unitId);
        $unit->delete();

        return redirect()->route('task-m.show', $project->id)->with('success', 'Progres dihapus.');
    }

    /**
     * Daftar task unit berdasarkan status (pending/done/cancelled)
     * untuk user tertentu (default: user yang sedang login).
     * Mendukung filter tahun (dari task_monitor.start_date) dan pagination.
     */
    public function unitsList(Request $request)
    {
        $status = $request->query('status');
        $userId = $request->query('user_id');
        $year   = $request->query('year');

        if (!in_array($status, ['pending', 'done', 'cancelled'], true)) {
            abort(400, 'Status tidak valid');
        }

        if (!$userId) {
            $userId = Auth::id();
        }
        $userId = (int) $userId;

        $this->authorizeOwner($userId, 'Tidak memiliki akses ke data user lain.');

        $units = TaskUnit::with('taskMonitor:id,title,user_id')
            ->whereHas('taskMonitor', function ($q) use ($userId, $year) {
                $q->where('user_id', $userId);
                if ($year) {
                    $q->whereYear('start_date', $year);
                }
            })
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        $user        = User::find($userId);
        $statusLabel = TaskUnit::STATUS_LABELS[$status];

        return view('task-m.units', compact('units', 'user', 'statusLabel', 'status', 'year', 'userId'));
    }

    /**
     * Laporan mingguan: yang SELESAI per minggu (minggu ini s/d N minggu lalu)
     * + daftar yang BELUM selesai beserta umurnya.
     * Minggu dihitung Senin - Minggu.
     */
    public function weeklyReport(Request $request)
    {
        $userId = (int) ($request->query('user_id') ?: Auth::id());

        $this->authorizeOwner($userId, 'Tidak memiliki akses ke data user lain.');

        $weeks = max(1, min((int) $request->query('weeks', 5), 12));
        $thisWeekStart = now()->startOfWeek(Carbon::MONDAY);
        $rangeStart    = $thisWeekStart->copy()->subWeeks($weeks - 1);
        $rangeEnd      = $thisWeekStart->copy()->endOfWeek(Carbon::SUNDAY);

        $base = fn () => TaskUnit::with('taskMonitor:id,title,start_date,end_date')
            ->whereHas('taskMonitor', fn ($q) => $q->where('user_id', $userId));

        $doneUnits = $base()
            ->where('status', 'done')
            ->whereBetween('completed_at', [$rangeStart, $rangeEnd])
            ->orderByDesc('completed_at')
            ->get();

        $createdUnits = $base()
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->get(['id', 'created_at']);

        $weekly = [];
        for ($i = 0; $i < $weeks; $i++) {
            $start = $thisWeekStart->copy()->subWeeks($i);
            $end   = $start->copy()->endOfWeek(Carbon::SUNDAY);

            $weekly[] = [
                'label'   => match ($i) { 0 => 'Minggu ini', 1 => 'Minggu lalu', default => "$i minggu lalu" },
                'start'   => $start,
                'end'     => $end,
                'done'    => $doneUnits->filter(fn ($u) => $u->completed_at->between($start, $end))->values(),
                'created' => $createdUnits->filter(fn ($u) => $u->created_at->between($start, $end))->count(),
            ];
        }

        $pendingUnits = $base()
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        $user = User::find($userId);

        return view('task-m.weekly-report', compact('weekly', 'pendingUnits', 'user', 'weeks', 'userId'));
    }
}
