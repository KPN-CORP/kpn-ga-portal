<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\HSRM\HsrmCertificate;
use App\Models\HSRM\HsrmEquipment;
use App\Models\AreaKerja;
use Illuminate\Http\Request;

class HsrmDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';
        $view = $request->input('view', 'certificates');

        $areaIds = $isAdmin ? null : $user->hsrmAreas->pluck('id_area_kerja')->toArray();

        $certData = null;
        if ($view === 'all' || $view === 'certificates') {
            $certQuery = HsrmCertificate::query();
            if (!$isAdmin) {
                $certQuery->whereIn('area_id', $areaIds);
            }
            $certs = $certQuery->get();

            $certData = [
                'total' => $certs->count(),
                'active' => $certs->filter(fn($c) => $c->expired_date > now()->addDays(30))->count(),
                'warning' => $certs->filter(fn($c) => $c->expired_date <= now()->addDays(30) && $c->expired_date > now())->count(),
                'expired' => $certs->filter(fn($c) => $c->expired_date <= now())->count(),
                'recommended' => $certs->filter(fn($c) => $c->rekomendasi === true)->count(),
                'not_recommended' => $certs->filter(fn($c) => $c->rekomendasi === false)->count(),
                'no_recommendation' => $certs->filter(fn($c) => $c->rekomendasi === null)->count(),
                'area_labels' => [],
                'area_data' => [],
            ];

            $areaCounts = $certs->groupBy('area_id')->map->count();
            foreach ($areaCounts as $areaId => $count) {
                $area = AreaKerja::find($areaId);
                if ($area) {
                    $certData['area_labels'][] = $area->nama_area;
                    $certData['area_data'][] = $count;
                }
            }
        }

        $eqData = null;
        if ($view === 'all' || $view === 'equipments') {
            $eqQuery = HsrmEquipment::query();
            if (!$isAdmin) {
                $eqQuery->whereIn('area_id', $areaIds);
            }
            $eqs = $eqQuery->get();

            $eqData = [
                'total' => $eqs->count(),
                'active' => $eqs->filter(fn($e) => $e->expired_date > now()->addDays(30))->count(),
                'warning' => $eqs->filter(fn($e) => $e->expired_date <= now()->addDays(30) && $e->expired_date > now())->count(),
                'expired' => $eqs->filter(fn($e) => $e->expired_date <= now())->count(),
                'recommended' => $eqs->filter(fn($e) => $e->rekomendasi === true)->count(),
                'not_recommended' => $eqs->filter(fn($e) => $e->rekomendasi === false)->count(),
                'no_recommendation' => $eqs->filter(fn($e) => $e->rekomendasi === null)->count(),
                'area_labels' => [],
                'area_data' => [],
            ];

            $areaCounts = $eqs->groupBy('area_id')->map->count();
            foreach ($areaCounts as $areaId => $count) {
                $area = AreaKerja::find($areaId);
                if ($area) {
                    $eqData['area_labels'][] = $area->nama_area;
                    $eqData['area_data'][] = $count;
                }
            }
        }

        $recentCerts = null;
        $recentEqs = null;

        if ($view === 'all' || $view === 'certificates') {
            $recentCerts = HsrmCertificate::with(['businessUnit', 'area', 'creator', 'certificateType'])
                ->when(!$isAdmin, function ($q) use ($areaIds) {
                    $q->whereIn('area_id', $areaIds);
                })
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();
        }

        if ($view === 'all' || $view === 'equipments') {
            $recentEqs = HsrmEquipment::with(['businessUnit', 'area', 'creator'])
                ->when(!$isAdmin, function ($q) use ($areaIds) {
                    $q->whereIn('area_id', $areaIds);
                })
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get();
        }

        return view('hsrm.dashboard', compact('certData', 'eqData', 'recentCerts', 'recentEqs', 'view'));
    }
}