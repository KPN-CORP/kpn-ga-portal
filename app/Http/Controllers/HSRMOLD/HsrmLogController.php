<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\HSRM\HsrmLog;

class HsrmLogController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';

        $query = HsrmLog::with('user')->orderBy('created_at', 'desc');

        if (!$isAdmin) {
            // Get area IDs where user is PIC
            $areaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
            // Filter logs where record belongs to those areas
            // We need to join with certificates/equipments or use subquery
            // Since logs don't have area_id, we need to filter by record_id and module
            $query->where(function ($q) use ($areaIds) {
                $q->where(function ($q2) use ($areaIds) {
                    $q2->where('module', 'certificate')
                       ->whereIn('record_id', function ($sub) use ($areaIds) {
                           $sub->select('id')
                               ->from('hsrm_certificates')
                               ->whereIn('area_id', $areaIds);
                       });
                })->orWhere(function ($q2) use ($areaIds) {
                    $q2->where('module', 'equipment')
                       ->whereIn('record_id', function ($sub) use ($areaIds) {
                           $sub->select('id')
                               ->from('hsrm_equipments')
                               ->whereIn('area_id', $areaIds);
                       });
                });
            });
        }

        $logs = $query->paginate(20);
        return view('hsrm.logs.index', compact('logs'));
    }
}