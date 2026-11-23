<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Helpers\HsrmRole;
use App\Models\AreaKerja;
use App\Models\HSRM\HsrmLog;
use App\Models\User;
use Illuminate\Http\Request;

class HsrmLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $canViewAll = HsrmRole::canViewAll();
        $areaIds = $canViewAll ? [] : $user->hsrmAreas->pluck('id_area_kerja')->toArray();

        $query = HsrmLog::with(['user', 'area'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        // PIC hanya melihat log area miliknya (hsrm_logs.area_id), termasuk log data yang sudah dihapus.
        if (!$canViewAll) {
            $query->whereIn('area_id', $areaIds);
        }

        if ($search = trim((string) $request->query('search'))) {
            $query->where('item_label', 'like', '%' . $search . '%');
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->query('area_id'));
        }
        if ($request->filled('module')) {
            $query->where('module', $request->query('module'));
        }
        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }
        if ($request->filled('user_id')) {
            $uid = $request->query('user_id');
            $uid === 'system' ? $query->whereNull('user_id') : $query->where('user_id', $uid);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        $logs = $query->paginate(20)->withQueryString();

        $areas = $canViewAll ? AreaKerja::orderBy('nama_area')->get() : $user->hsrmAreas;
        $userIds = HsrmLog::select('user_id')->whereNotNull('user_id')->distinct()
            ->when(!$canViewAll, fn ($q) => $q->whereIn('area_id', $areaIds));
        $users = User::whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']);
        $actions = collect(HsrmLog::EVENTS)->map(fn ($e) => $e[0]);

        return view('hsrm.logs.index', compact('logs', 'areas', 'users', 'actions'));
    }
}
