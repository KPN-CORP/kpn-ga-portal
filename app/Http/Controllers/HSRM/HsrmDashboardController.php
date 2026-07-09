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

        // =============================================
        // CERTIFICATES DATA
        // =============================================
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

            // Area untuk Certificates (count)
            $areaCounts = $certs->groupBy('area_id')->map->count();
            foreach ($areaCounts as $areaId => $count) {
                $area = AreaKerja::find($areaId);
                if ($area) {
                    $certData['area_labels'][] = $area->nama_area;
                    $certData['area_data'][] = $count;
                }
            }
        }

        // =============================================
        // EQUIPMENTS DATA (dengan Total Items)
        // =============================================
        $eqData = null;
        if ($view === 'all' || $view === 'equipments') {
            $eqQuery = HsrmEquipment::query();
            if (!$isAdmin) {
                $eqQuery->whereIn('area_id', $areaIds);
            }
            $eqs = $eqQuery->get();

            // Inisialisasi total items
            $totalItemsAll = 0;
            $totalItemsActive = 0;
            $totalItemsWarning = 0;
            $totalItemsExpired = 0;
            $totalItemsRecommended = 0;
            $totalItemsNotRecommended = 0;
            $totalItemsNoRecommendation = 0;

            foreach ($eqs as $eq) {
                $items = $eq->total_items ?? 1; // default 1 jika null

                $totalItemsAll += $items;

                // Status berdasarkan expired_date
                if ($eq->expired_date > now()->addDays(30)) {
                    $totalItemsActive += $items;
                } elseif ($eq->expired_date <= now()->addDays(30) && $eq->expired_date > now()) {
                    $totalItemsWarning += $items;
                } else {
                    $totalItemsExpired += $items;
                }

                // Rekomendasi
                if ($eq->rekomendasi === true) {
                    $totalItemsRecommended += $items;
                } elseif ($eq->rekomendasi === false) {
                    $totalItemsNotRecommended += $items;
                } else {
                    $totalItemsNoRecommendation += $items;
                }
            }

            // === PERUBAHAN UTAMA: Area menggunakan SUM(total_items) ===
            $areaData = $eqs->groupBy('area_id')->map(function ($items) {
                return $items->sum('total_items'); // sum total_items per area
            });

            $eqData = [
                // Count (untuk keperluan filter & stat dasar)
                'total' => $eqs->count(),
                'active' => $eqs->filter(fn($e) => $e->expired_date > now()->addDays(30))->count(),
                'warning' => $eqs->filter(fn($e) => $e->expired_date <= now()->addDays(30) && $e->expired_date > now())->count(),
                'expired' => $eqs->filter(fn($e) => $e->expired_date <= now())->count(),
                'recommended' => $eqs->filter(fn($e) => $e->rekomendasi === true)->count(),
                'not_recommended' => $eqs->filter(fn($e) => $e->rekomendasi === false)->count(),
                'no_recommendation' => $eqs->filter(fn($e) => $e->rekomendasi === null)->count(),
                
                // Total Items (untuk stat card & grafik)
                'total_items_all' => $totalItemsAll,
                'total_items_active' => $totalItemsActive,
                'total_items_warning' => $totalItemsWarning,
                'total_items_expired' => $totalItemsExpired,
                'total_items_recommended' => $totalItemsRecommended,
                'total_items_not_recommended' => $totalItemsNotRecommended,
                'total_items_no_recommendation' => $totalItemsNoRecommendation,
                
                // Area (menggunakan total items, bukan count)
                'area_labels' => [],
                'area_data' => [],
            ];

            // Isi area_labels dan area_data dari hasil perhitungan SUM(total_items)
            foreach ($areaData as $areaId => $totalItems) {
                $area = AreaKerja::find($areaId);
                if ($area) {
                    $eqData['area_labels'][] = $area->nama_area;
                    $eqData['area_data'][] = $totalItems;
                }
            }
        }

        // =============================================
        // RECENT ITEMS
        // =============================================
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