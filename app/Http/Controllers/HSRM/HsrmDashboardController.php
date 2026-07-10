<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\HSRM\HsrmCertificate;
use App\Models\HSRM\HsrmEquipment;
use App\Models\HSRM\HsrmCertificateQuota;
use App\Models\HSRM\HsrmEquipmentQuota;
use App\Models\AreaKerja;
use Illuminate\Http\Request;

class HsrmDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';
        $view = $request->input('view', 'certificates');

        // Jika view = 'budget' dan bukan admin, redirect ke certificates
        if ($view === 'budget' && !$isAdmin) {
            return redirect()->route('hsrm.dashboard', ['view' => 'certificates']);
        }

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
                'recommended' => $certs->filter(fn($c) => $c->rekomendasi === 'recommended')->count(),
                'not_recommended' => $certs->filter(fn($c) => $c->rekomendasi === 'not_recommended')->count(),
                'valid' => $certs->filter(fn($c) => $c->rekomendasi === 'valid')->count(),
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
            $totalItemsValid = 0;

            foreach ($eqs as $eq) {
                $items = $eq->total_items ?? 1;

                $totalItemsAll += $items;

                // Status berdasarkan expired_date
                if ($eq->expired_date > now()->addDays(30)) {
                    $totalItemsActive += $items;
                } elseif ($eq->expired_date <= now()->addDays(30) && $eq->expired_date > now()) {
                    $totalItemsWarning += $items;
                } else {
                    $totalItemsExpired += $items;
                }

                // Rekomendasi (sesuai enum)
                if ($eq->rekomendasi === 'recommended') {
                    $totalItemsRecommended += $items;
                } elseif ($eq->rekomendasi === 'not_recommended') {
                    $totalItemsNotRecommended += $items;
                } elseif ($eq->rekomendasi === 'valid') {
                    $totalItemsValid += $items;
                }
                // jika null, tidak dihitung
            }

            // Area menggunakan SUM(total_items)
            $areaData = $eqs->groupBy('area_id')->map(function ($items) {
                return $items->sum('total_items');
            });

            $eqData = [
                // Count (untuk keperluan filter & stat dasar)
                'total' => $eqs->count(),
                'active' => $eqs->filter(fn($e) => $e->expired_date > now()->addDays(30))->count(),
                'warning' => $eqs->filter(fn($e) => $e->expired_date <= now()->addDays(30) && $e->expired_date > now())->count(),
                'expired' => $eqs->filter(fn($e) => $e->expired_date <= now())->count(),
                'recommended' => $eqs->filter(fn($e) => $e->rekomendasi === 'recommended')->count(),
                'not_recommended' => $eqs->filter(fn($e) => $e->rekomendasi === 'not_recommended')->count(),
                'valid' => $eqs->filter(fn($e) => $e->rekomendasi === 'valid')->count(),

                // Total Items (untuk stat card & grafik)
                'total_items_all' => $totalItemsAll,
                'total_items_active' => $totalItemsActive,
                'total_items_warning' => $totalItemsWarning,
                'total_items_expired' => $totalItemsExpired,
                'total_items_recommended' => $totalItemsRecommended,
                'total_items_not_recommended' => $totalItemsNotRecommended,
                'total_items_valid' => $totalItemsValid,

                // Area (menggunakan total items, bukan count)
                'area_labels' => [],
                'area_data' => [],
            ];

            // Isi area_labels dan area_data
            foreach ($areaData as $areaId => $totalItems) {
                $area = AreaKerja::find($areaId);
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

        if ($view === 'budget' || $view === 'all') {
            if ($isAdmin) {
                // --- Ambil semua area yang memiliki certificate quota atau equipment quota (tanpa syarat budget) ---
                $allAreaIds = collect();

                // Certificate quotas
                $certQuotaAreas = HsrmCertificateQuota::distinct('area_id')->pluck('area_id');
                $eqQuotaAreas = HsrmEquipmentQuota::distinct('area_id')->pluck('area_id');
                $allAreaIds = $certQuotaAreas->merge($eqQuotaAreas)->unique();

                // Jika tidak ada quota sama sekali, coba ambil semua area yang memiliki sertifikat/equipment (fallback)
                if ($allAreaIds->isEmpty()) {
                    $allAreaIds = AreaKerja::pluck('id_area_kerja');
                }

                $areas = AreaKerja::whereIn('id_area_kerja', $allAreaIds)->get();

                // Data untuk grafik Budget (hanya area dengan budget > 0)
                $budgetItems = collect();
                // Data untuk grafik Certificate Quota (semua area dengan quota atau active)
                $certQuotaItems = collect();
                // Data untuk grafik Equipment Quota (semua area dengan quota atau active)
                $eqQuotaItems = collect();

                foreach ($areas as $area) {
                    // Ambil semua certificate quotas untuk area ini
                    $certQuotas = HsrmCertificateQuota::where('area_id', $area->id_area_kerja)->get();
                    // Ambil semua equipment quotas untuk area ini
                    $eqQuotas = HsrmEquipmentQuota::where('area_id', $area->id_area_kerja)->get();

                    // Hitung total budget (dari certificate + equipment)
                    $totalBudget = $certQuotas->sum('budget') + $eqQuotas->sum('budget');

                    // Hitung total quota & active untuk certificate (sum semua tipe)
                    $certQuotaTotal = $certQuotas->sum('quota');
                    $certActive = HsrmCertificate::where('area_id', $area->id_area_kerja)
                                    ->where('status_verif', 'verified')
                                    ->where('expired_date', '>', now())
                                    ->count();

                    // Hitung total quota & active untuk equipment (sum semua tipe)
                    $eqQuotaTotal = $eqQuotas->sum('quota');
                    $eqActive = HsrmEquipment::where('area_id', $area->id_area_kerja)
                                ->where('status_verif', 'verified')
                                ->where('expired_date', '>', now())
                                ->sum('total_items');

                    // Untuk budget, hanya jika totalBudget > 0
                    if ($totalBudget > 0) {
                        $budgetItems->push((object) [
                            'area_name' => $area->nama_area,
                            'total_budget' => $totalBudget,
                        ]);
                    }

                    // Untuk certificate quota, jika ada quota atau active > 0
                    if ($certQuotaTotal > 0 || $certActive > 0) {
                        $certQuotaItems->push((object) [
                            'area_name' => $area->nama_area,
                            'certificate_quota' => $certQuotaTotal,
                            'certificate_active' => $certActive,
                        ]);
                    }

                    // Untuk equipment quota, jika ada quota atau active > 0
                    if ($eqQuotaTotal > 0 || $eqActive > 0) {
                        $eqQuotaItems->push((object) [
                            'area_name' => $area->nama_area,
                            'equipment_quota' => $eqQuotaTotal,
                            'equipment_active' => $eqActive,
                        ]);
                    }
                }

                $budgetData = $budgetItems;
                $certQuotaData = $certQuotaItems;
                $eqQuotaData = $eqQuotaItems;
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

        return view('hsrm.dashboard', compact('certData', 'eqData', 'recentCerts', 'recentEqs', 'view', 'budgetData', 'certQuotaData', 'eqQuotaData'));
    }
}