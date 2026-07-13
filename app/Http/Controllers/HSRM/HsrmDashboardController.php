<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\HSRM\HsrmCertificate;
use App\Models\HSRM\HsrmEquipment;
use App\Models\AreaKerja;
use App\Models\HSRM\HsrmCertificateQuota;
use App\Models\HSRM\HsrmEquipmentQuota;
use Illuminate\Http\Request;

class HsrmDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';
        $view = $request->input('view', 'certificates');
        $areaId = $request->input('area_id');
        $selectedArea = null;

        // --- Ambil daftar area untuk dropdown (hanya untuk admin) ---
        $areas = $isAdmin ? AreaKerja::orderBy('nama_area')->get() : collect();

        // --- Jika admin dan ada area_id yang dipilih ---
        if ($isAdmin && $areaId) {
            $selectedArea = AreaKerja::find($areaId);
        }

        // --- Dapatkan area_ids yang diizinkan untuk PIC ---
        $areaIds = $isAdmin ? null : $user->hsrmAreas->pluck('id_area_kerja')->toArray();

        // =============================================
        // CERTIFICATES DATA
        // =============================================
        $certData = null;
        if ($view === 'all' || $view === 'certificates') {
            $certQuery = HsrmCertificate::query();
            if (!$isAdmin) {
                $certQuery->whereIn('area_id', $areaIds);
            } elseif ($selectedArea) {
                $certQuery->where('area_id', $selectedArea->id_area_kerja);
            }
            $certs = $certQuery->get();

            $certData = [
                'total' => $certs->count(),
                'active' => $certs->filter(fn($c) => $c->expired_date > now()->addDays(30))->count(),
                'warning' => $certs->filter(fn($c) => $c->expired_date <= now()->addDays(30) && $c->expired_date > now())->count(),
                'expired' => $certs->filter(fn($c) => $c->expired_date <= now())->count(),
                'recommended' => $certs->filter(fn($c) => $c->rekomendasi === 'recommended')->count(),
                'not_recommended' => $certs->filter(fn($c) => $c->rekomendasi === 'not_recommended')->count(),
                'valid' => $certs->filter(fn($c) => $c->rekomendasi === 'valid')->count(),
                'area_labels' => [],
                'area_data' => [],
            ];

            // Area untuk Certificates (count)
            $areaCounts = $certs->groupBy('area_id')->map->count();
            foreach ($areaCounts as $areaIdKey => $count) {
                $area = AreaKerja::find($areaIdKey);
                if ($area) {
                    $certData['area_labels'][] = $area->nama_area;
                    $certData['area_data'][] = $count;
                }
            }
        }

        // =============================================
        // EQUIPMENTS DATA
        // =============================================
        $eqData = null;
        if ($view === 'all' || $view === 'equipments') {
            $eqQuery = HsrmEquipment::query();
            if (!$isAdmin) {
                $eqQuery->whereIn('area_id', $areaIds);
            } elseif ($selectedArea) {
                $eqQuery->where('area_id', $selectedArea->id_area_kerja);
            }
            $eqs = $eqQuery->get();

            $totalItemsAll = 0;
            $totalItemsActive = 0;
            $totalItemsWarning = 0;
            $totalItemsExpired = 0;
            $totalItemsRecommended = 0;
            $totalItemsNotRecommended = 0;
            $totalItemsValid = 0;

            foreach ($eqs as $eq) {
                $items = $eq->total_items ?? 1;
                $totalItemsAll += $items;

                if ($eq->expired_date > now()->addDays(30)) {
                    $totalItemsActive += $items;
                } elseif ($eq->expired_date <= now()->addDays(30) && $eq->expired_date > now()) {
                    $totalItemsWarning += $items;
                } else {
                    $totalItemsExpired += $items;
                }

                if ($eq->rekomendasi === 'recommended') {
                    $totalItemsRecommended += $items;
                } elseif ($eq->rekomendasi === 'not_recommended') {
                    $totalItemsNotRecommended += $items;
                } else {
                    $totalItemsValid += $items;
                }
            }

            $areaData = $eqs->groupBy('area_id')->map(fn($items) => $items->sum('total_items'));

            $eqData = [
                'total' => $eqs->count(),
                'active' => $eqs->filter(fn($e) => $e->expired_date > now()->addDays(30))->count(),
                'warning' => $eqs->filter(fn($e) => $e->expired_date <= now()->addDays(30) && $e->expired_date > now())->count(),
                'expired' => $eqs->filter(fn($e) => $e->expired_date <= now())->count(),
                'recommended' => $eqs->filter(fn($e) => $e->rekomendasi === 'recommended')->count(),
                'not_recommended' => $eqs->filter(fn($e) => $e->rekomendasi === 'not_recommended')->count(),
                'valid' => $eqs->filter(fn($e) => $e->rekomendasi === 'valid')->count(),
                'total_items_all' => $totalItemsAll,
                'total_items_active' => $totalItemsActive,
                'total_items_warning' => $totalItemsWarning,
                'total_items_expired' => $totalItemsExpired,
                'total_items_recommended' => $totalItemsRecommended,
                'total_items_not_recommended' => $totalItemsNotRecommended,
                'total_items_valid' => $totalItemsValid,
                'area_labels' => [],
                'area_data' => [],
            ];

            foreach ($areaData as $areaIdKey => $totalItems) {
                $area = AreaKerja::find($areaIdKey);
                if ($area) {
                    $eqData['area_labels'][] = $area->nama_area;
                    $eqData['area_data'][] = $totalItems;
                }
            }
        }

        // =============================================
        // BUDGET & QUOTA DATA (Admin only)
        // =============================================
        $budgetData = null;
        $certQuotaData = null;
        $eqQuotaData = null;

        if ($isAdmin && ($view === 'budget' || $view === 'all')) {
            // Budget per area (total budget certificate + equipment)
            $areasForBudget = $selectedArea ? collect([$selectedArea]) : AreaKerja::all();
            $budgetItems = [];

            foreach ($areasForBudget as $area) {
                $certBudget = HsrmCertificateQuota::where('area_id', $area->id_area_kerja)->sum('budget');
                $eqBudget = HsrmEquipmentQuota::where('area_id', $area->id_area_kerja)->sum('budget');
                $total = ($certBudget ?? 0) + ($eqBudget ?? 0);
                if ($total > 0) {
                    $budgetItems[] = (object) [
                        'area_name' => $area->nama_area,
                        'total_budget' => $total,
                    ];
                }
            }
            $budgetData = collect($budgetItems);

            // Certificate Quota vs Active
            $certQuotaItems = [];
            foreach ($areasForBudget as $area) {
                $quota = HsrmCertificateQuota::where('area_id', $area->id_area_kerja)->sum('quota');
                $active = HsrmCertificate::where('area_id', $area->id_area_kerja)
                            ->where('status_verif', 'verified')
                            ->where('expired_date', '>', now())
                            ->count();
                if ($quota > 0 || $active > 0) {
                    $certQuotaItems[] = (object) [
                        'area_name' => $area->nama_area,
                        'certificate_quota' => $quota,
                        'certificate_active' => $active,
                    ];
                }
            }
            $certQuotaData = collect($certQuotaItems);

            // Equipment Quota vs Active (total items)
            $eqQuotaItems = [];
            foreach ($areasForBudget as $area) {
                $quota = HsrmEquipmentQuota::where('area_id', $area->id_area_kerja)->sum('quota');
                $active = HsrmEquipment::where('area_id', $area->id_area_kerja)
                            ->where('status_verif', 'verified')
                            ->where('expired_date', '>', now())
                            ->sum('total_items');
                if ($quota > 0 || $active > 0) {
                    $eqQuotaItems[] = (object) [
                        'area_name' => $area->nama_area,
                        'equipment_quota' => $quota,
                        'equipment_active' => $active,
                    ];
                }
            }
            $eqQuotaData = collect($eqQuotaItems);
        }

        // =============================================
        // RECENT ITEMS
        // =============================================
        $recentCerts = null;
        $recentEqs = null;

        if ($view === 'all' || $view === 'certificates') {
            $query = HsrmCertificate::with(['businessUnit', 'area', 'creator', 'certificateType']);
            if (!$isAdmin) {
                $query->whereIn('area_id', $areaIds);
            } elseif ($selectedArea) {
                $query->where('area_id', $selectedArea->id_area_kerja);
            }
            $recentCerts = $query->orderBy('updated_at', 'desc')->limit(10)->get();
        }

        if ($view === 'all' || $view === 'equipments') {
            $query = HsrmEquipment::with(['businessUnit', 'area', 'creator']);
            if (!$isAdmin) {
                $query->whereIn('area_id', $areaIds);
            } elseif ($selectedArea) {
                $query->where('area_id', $selectedArea->id_area_kerja);
            }
            $recentEqs = $query->orderBy('updated_at', 'desc')->limit(10)->get();
        }

        return view('hsrm.dashboard', compact(
            'certData',
            'eqData',
            'recentCerts',
            'recentEqs',
            'view',
            'areas',
            'selectedArea',
            'budgetData',
            'certQuotaData',
            'eqQuotaData'
        ));
    }
}