<?php

namespace App\Http\Controllers\Task_M;

use App\Http\Controllers\Controller;
use App\Models\Task_M\TaskMonitor;
use App\Models\Task_M\TaskUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskMonitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // Halaman utama: superadmin -> daftar user, user biasa -> project sendiri
    public function index(Request $request)
    {
        if (Auth::user()->isTaskMonitorSuperadmin()) {
            $users = User::whereHas('taskMonitors')->with('taskMonitors.units')->get();
            return view('task-m.users', compact('users'));
        } else {
            $year = $request->get('year');
            $query = TaskMonitor::where('user_id', Auth::id())->with('units');
            if ($year) {
                $query->whereYear('start_date', $year);
            }
            $projects = $query->orderBy('created_at', 'desc')->get();
            $stats = TaskMonitor::getUserStats(Auth::id(), $year);
            $availableYears = TaskMonitor::getAvailableYears(Auth::id());
            return view('task-m.index', compact('projects', 'stats', 'availableYears', 'year'));
        }
    }

    // Menampilkan project milik user tertentu (hanya superadmin)
    public function userProjects(Request $request, $userId)
    {
        if (!Auth::user()->isTaskMonitorSuperadmin()) {
            abort(403, 'Hanya superadmin yang bisa mengakses halaman ini.');
        }
        $year = $request->get('year');
        $query = TaskMonitor::where('user_id', $userId)->with('units');
        if ($year) {
            $query->whereYear('start_date', $year);
        }
        $projects = $query->orderBy('created_at', 'desc')->get();
        $stats = TaskMonitor::getUserStats($userId, $year);
        $user = User::findOrFail($userId);
        $availableYears = TaskMonitor::getAvailableYears($userId);
        return view('task-m.index', compact('projects', 'stats', 'user', 'availableYears', 'year'));
    }

    // Store project baru
    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        TaskMonitor::create([
            'user_id'    => Auth::id(),
            'title'      => $request->title,
            'start_date' => $request->start_date,
            'end_date'   => $request->end_date,
        ]);

        if (Auth::user()->isTaskMonitorSuperadmin()) {
            return redirect()->route('task-m.user.projects', Auth::id())->with('success', 'Project berhasil dibuat.');
        }
        return redirect()->route('task-m.index')->with('success', 'Project berhasil dibuat.');
    }

    // Detail project
    public function show($id)
    {
        $project = TaskMonitor::with('units')->findOrFail($id);
        if (!Auth::user()->isTaskMonitorSuperadmin() && $project->user_id !== Auth::id()) {
            abort(403);
        }
        return view('task-m.show', compact('project'));
    }

    // Update project (judul & tanggal)
    public function update(Request $request, $id)
    {
        $project = TaskMonitor::findOrFail($id);
        if (!Auth::user()->isTaskMonitorSuperadmin() && $project->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'title'      => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $project->update($request->only(['title', 'start_date', 'end_date']));
        return redirect()->route('task-m.show', $project->id)->with('success', 'Project diperbarui.');
    }

    // Hapus project
    public function destroy($id)
    {
        $project = TaskMonitor::findOrFail($id);
        if (!Auth::user()->isTaskMonitorSuperadmin() && $project->user_id !== Auth::id()) {
            abort(403);
        }
        $project->delete();

        if (Auth::user()->isTaskMonitorSuperadmin()) {
            return redirect()->route('task-m.user.projects', Auth::id())->with('success', 'Project dihapus.');
        }
        return redirect()->route('task-m.index')->with('success', 'Project dihapus.');
    }

    // ---- Unit management ----
    public function addUnit(Request $request, $projectId)
    {
        $project = TaskMonitor::findOrFail($projectId);
        if (!Auth::user()->isTaskMonitorSuperadmin() && $project->user_id !== Auth::id()) {
            abort(403);
        }

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
        $project = TaskMonitor::findOrFail($projectId);
        if (!Auth::user()->isTaskMonitorSuperadmin() && $project->user_id !== Auth::id()) {
            abort(403);
        }

        $unit = TaskUnit::where('task_monitor_id', $project->id)->findOrFail($unitId);
        if ($unit->isFinal()) {
            return redirect()->route('task-m.show', $project->id)->with('error', 'Unit sudah final, tidak bisa diubah statusnya.');
        }

        $action = $request->action;
        if ($action === 'done') {
            $unit->status = 'done';
        } elseif ($action === 'cancel') {
            $unit->status = 'cancelled';
        } else {
            return redirect()->route('task-m.show', $project->id)->with('error', 'Aksi tidak valid.');
        }
        $unit->save();

        return redirect()->route('task-m.show', $project->id)->with('success', 'Status progres diperbarui.');
    }

    public function updateUnitDescription(Request $request, $projectId, $unitId)
    {
        $project = TaskMonitor::findOrFail($projectId);
        if (!Auth::user()->isTaskMonitorSuperadmin() && $project->user_id !== Auth::id()) {
            abort(403);
        }

        $unit = TaskUnit::where('task_monitor_id', $project->id)->findOrFail($unitId);
        $request->validate(['description' => 'required|string|max:500']);
        $unit->description = $request->description;
        $unit->save();

        return redirect()->route('task-m.show', $project->id)->with('success', 'Deskripsi progres diperbarui.');
    }

    public function deleteUnit($projectId, $unitId)
    {
        $project = TaskMonitor::findOrFail($projectId);
        if (!Auth::user()->isTaskMonitorSuperadmin() && $project->user_id !== Auth::id()) {
            abort(403);
        }

        $unit = TaskUnit::where('task_monitor_id', $project->id)->findOrFail($unitId);
        $unit->delete();

        return redirect()->route('task-m.show', $project->id)->with('success', 'Progres dihapus.');
    }

    /**
     * Menampilkan daftar task unit berdasarkan status (pending/done)
     * untuk user tertentu (default: user yang sedang login)
     * Mendukung filter tahun (dari task_monitor.start_date)
     */
    public function unitsList(Request $request)
    {
        $status = $request->query('status');
        $userId = $request->query('user_id');
        $year = $request->query('year');

        if (!in_array($status, ['pending', 'done'])) {
            abort(400, 'Status tidak valid');
        }

        if (!$userId) {
            $userId = Auth::id();
        }

        if ((int)$userId !== Auth::id() && !Auth::user()->isTaskMonitorSuperadmin()) {
            abort(403, 'Tidak memiliki akses ke data user lain.');
        }

        $unitsQuery = TaskUnit::with('taskMonitor')
            ->whereHas('taskMonitor', function ($q) use ($userId, $year) {
                $q->where('user_id', $userId);
                if ($year) {
                    $q->whereYear('start_date', $year);
                }
            })
            ->where('status', $status)
            ->orderBy('created_at', 'desc');

        $units = $unitsQuery->get();
        $user = User::find($userId);
        $statusLabel = $status === 'pending' ? 'Dalam Proses' : 'Selesai';

        return view('task-m.units', compact('units', 'user', 'statusLabel', 'status', 'year'));
    }
}