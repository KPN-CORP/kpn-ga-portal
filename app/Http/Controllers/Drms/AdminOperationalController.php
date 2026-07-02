<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\TripLog;
use App\Models\Drms\VehicleService;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\Vehicle;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminOperationalController extends Controller
{
    /**
     * Dashboard Grafik Operasional dengan filter bulan/tahun
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $this->getBusinessUnitId($user);
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $stats = $this->getOperationalStats($businessUnitId, $month, $year);
        $chartData = $this->getMonthlyChartData($businessUnitId);
        $efficiencyData = $this->getEfficiencyData($businessUnitId);
        $transportDistribution = $this->getTransportDistribution($businessUnitId, $month, $year);
        $recentLogs = $this->getRecentLogs($businessUnitId, 5);

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = date('F', mktime(0, 0, 0, $i, 1));
        }
        $years = range(now()->year - 2, now()->year);

        $isSuperAdmin = $user->isDrmsSuperAdmin();

        return view('drms.admin.operational_dashboard', compact(
            'stats', 'chartData', 'efficiencyData',
            'transportDistribution', 'months', 'years', 'month', 'year',
            'recentLogs', 'isSuperAdmin'
        ));
    }

    /**
     * Export CSV
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $this->getBusinessUnitId($user);
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $stats = $this->getOperationalStats($businessUnitId, $month, $year);
        $efficiencyData = $this->getEfficiencyData($businessUnitId);
        $transportDistribution = $this->getTransportDistribution($businessUnitId, $month, $year);

        $filename = 'laporan_operasional_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $handle = fopen('php://output', 'w');
        fputcsv($handle, ['LAPORAN OPERASIONAL DRMS']);
        fputcsv($handle, ['Periode: ' . date('F Y', mktime(0, 0, 0, $month, 1, $year))]);
        if ($businessUnitId) {
            $buName = \App\Models\BisnisUnit::find($businessUnitId)->nama_bisnis_unit ?? 'Unit';
            fputcsv($handle, ['Business Unit: ' . $buName]);
        } else {
            fputcsv($handle, ['Business Unit: SEMUA (Superadmin)']);
        }
        fputcsv($handle, []);
        
        fputcsv($handle, ['RINGKASAN']);
        fputcsv($handle, ['Total Biaya Operasi', 'Rp ' . number_format($stats['total_operational_cost'], 0, ',', '.')]);
        fputcsv($handle, ['Total BBM/Charge', 'Rp ' . number_format($stats['total_fuel_cost'], 0, ',', '.')]);
        fputcsv($handle, ['Total Service', 'Rp ' . number_format($stats['total_service_cost'], 0, ',', '.')]);
        fputcsv($handle, ['Total Jarak Tempuh', number_format($stats['total_distance'], 0, ',', '.') . ' km']);
        fputcsv($handle, ['Menunggu Verifikasi', $stats['pending_verification']]);
        fputcsv($handle, []);
        
        fputcsv($handle, ['DISTRIBUSI TRANSPORTASI (Periode ' . date('F Y', mktime(0,0,0,$month,1,$year)) . ')']);
        fputcsv($handle, ['Tipe', 'Jumlah']);
        foreach ($transportDistribution as $item) {
            fputcsv($handle, [
                $item->transport_type ? ucfirst(str_replace('_', ' ', $item->transport_type)) : 'Tidak Diketahui',
                $item->total
            ]);
        }
        fputcsv($handle, []);
        
        fputcsv($handle, ['EFISIENSI KENDARAAN (TOP 10)']);
        fputcsv($handle, ['Kendaraan', 'Rata-rata Efisiensi (km/liter atau km/kWh)', 'Total Perjalanan']);
        foreach ($efficiencyData as $item) {
            fputcsv($handle, [
                $item['vehicle'],
                $item['avg_efficiency'],
                $item['total_trips']
            ]);
        }
        
        fclose($handle);
        exit;
    }

    /**
     * Monitoring Trip Log
     */
    public function monitoringLogs(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $this->getBusinessUnitId($user);

        $query = TripLog::with(['request.requester', 'request.driver', 'request.vehicle'])
            ->whereHas('request', function ($q) use ($businessUnitId) {
                if ($businessUnitId) {
                    $this->applyBusinessUnitFilter($q, $businessUnitId);
                }
            });

        if ($request->has('status')) {
            if ($request->status == 'pending') {
                $query->where('is_submitted', 1)->where('is_verified', 0);
            } elseif ($request->status == 'verified') {
                $query->where('is_verified', 1);
            } elseif ($request->status == 'draft') {
                $query->where('is_submitted', 0)->where('is_verified', 0);
            } elseif ($request->status == 'revision') {
                $query->where('is_submitted', 0)->where('is_verified', 0)->whereNotNull('revision_note');
            }
        }

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->whereHas('request', function ($q) use ($search) {
                $q->where('request_no', 'LIKE', $search)
                  ->orWhereHas('driver', function ($q2) use ($search) {
                      $q2->where('name', 'LIKE', $search);
                  });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest()->paginate(20);
        return view('drms.admin.monitoring_logs', compact('logs'));
    }

    public function verifyLogForm($logId)
    {
        $log = TripLog::with(['request.requester', 'request.driver', 'request.vehicle'])
            ->findOrFail($logId);
        $this->authorizeLogAccess($log);
        return view('drms.admin.verify_log', compact('log'));
    }

    public function verifyLog(Request $request, $logId)
    {
        $log = TripLog::findOrFail($logId);
        $this->authorizeLogAccess($log);

        $action = $request->input('action');
        $notes = $request->input('verification_notes');

        DB::beginTransaction();
        try {
            if ($action == 'approve') {
                $log->is_verified = 1;
                $log->verified_by = Auth::id();
                $log->verified_at = now();
                $log->verification_notes = $notes;
                $log->revision_note = null;
                $log->revision_requested_at = null;
                $message = 'Log berhasil diverifikasi.';
            } else {
                $log->is_verified = 0;
                $log->is_submitted = 0;
                $log->verified_by = null;
                $log->verified_at = null;
                $log->verification_notes = $notes;
                $log->revision_note = $notes;
                $log->revision_requested_at = now();
                $message = 'Log dikembalikan ke driver untuk revisi.';
            }
            $log->save();
            DB::commit();
            return redirect()->route('drms.admin.monitoring.logs')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal memverifikasi: ' . $e->getMessage());
        }
    }

    public function vehicleServices(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $this->getBusinessUnitId($user);

        $query = VehicleService::with(['vehicle', 'creator'])
            ->when($businessUnitId, function ($q) use ($businessUnitId) {
                return $q->where('business_unit_id', $businessUnitId);
            });

        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('vehicle', function ($q2) use ($search) {
                    $q2->where('plate_number', 'LIKE', $search);
                })->orWhere('description', 'LIKE', $search);
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('service_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('service_date', '<=', $request->date_to);
        }

        $services = $query->latest()->paginate(20);
        $vehicles = Vehicle::when($businessUnitId, function ($q) use ($businessUnitId) {
            return $q->where('business_unit_id', $businessUnitId);
        })->get();

        return view('drms.admin.vehicle_services', compact('services', 'vehicles'));
    }

    public function storeVehicleService(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $this->getBusinessUnitId($user);

        $this->validate($request, [
            'vehicle_id' => 'required|exists:drms_vehicles,id',
            'service_date' => 'required|date',
            'odometer_at_service' => 'nullable|integer|min:0',
            'cost' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'photo_evidence' => 'nullable|image|max:5120',
        ]);

        DB::beginTransaction();
        try {
            $service = new VehicleService();
            $service->fill($request->only([
                'vehicle_id', 'service_date', 'odometer_at_service',
                'cost', 'description'
            ]));
            $service->business_unit_id = $businessUnitId;
            $service->created_by = Auth::id();

            if ($request->hasFile('photo_evidence')) {
                $service->photo_evidence = ImageHelper::compressAndStore(
                    $request->file('photo_evidence'),
                    'vehicle_services'
                );
            }

            $service->save();
            DB::commit();
            return redirect()->route('drms.admin.vehicle.services')->with('success', 'Data service berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Gagal menyimpan: ' . $e->getMessage());
        }
    }

    public function deleteVehicleService($id)
    {
        $service = VehicleService::findOrFail($id);
        $this->authorizeServiceAccess($service);

        if ($service->photo_evidence) {
            ImageHelper::deleteImage($service->photo_evidence);
        }
        $service->delete();
        return redirect()->route('drms.admin.vehicle.services')->with('success', 'Data service dihapus.');
    }

    // ============== HELPER METHODS ==============

    private function getBusinessUnitId($user)
    {
        if ($user->isDrmsSuperAdmin()) {
            return null;
        }
        $profile = $user->drmsProfile;
        return $profile->business_unit_id ?? abort(403, 'Anda tidak memiliki unit bisnis.');
    }

    /**
     * Terapkan filter business unit pada query request
     * Mencakup current_business_unit_id atau requester BU
     */
    private function applyBusinessUnitFilter($query, $buId)
    {
        $query->where(function ($q) use ($buId) {
            $q->where('current_business_unit_id', $buId)
              ->orWhere(function ($sub) use ($buId) {
                  $sub->whereNull('current_business_unit_id')
                      ->whereHas('requester.drmsProfile', function ($q2) use ($buId) {
                          $q2->where('business_unit_id', $buId);
                      });
              });
        });
    }

    private function authorizeLogAccess($log)
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin()) return;

        $buId = $user->drmsProfile->business_unit_id;
        $logBu = $log->request->current_business_unit_id ?? $log->request->requester->drmsProfile->business_unit_id;
        if ($logBu != $buId) {
            abort(403, 'Anda tidak memiliki akses ke log ini.');
        }
    }

    private function authorizeServiceAccess($service)
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin()) return;

        $buId = $user->drmsProfile->business_unit_id;
        if ($service->business_unit_id != $buId) {
            abort(403, 'Anda tidak memiliki akses ke service ini.');
        }
    }

    // ============== DATA GRAFIK ==============

    private function getOperationalStats($buId, $month, $year)
    {
        $fuelQuery = TripLog::where('is_verified', 1)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);
        if ($buId) {
            $fuelQuery->whereHas('request', function ($q) use ($buId) {
                $this->applyBusinessUnitFilter($q, $buId);
            });
        }
        $totalFuel = $fuelQuery->sum('fuel_cost');
        $totalDistance = $fuelQuery->sum(DB::raw('odometer_finish - odometer_start'));

        $serviceQuery = VehicleService::query()
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);
        if ($buId) {
            $serviceQuery->where('business_unit_id', $buId);
        }
        $totalService = $serviceQuery->sum('cost');

        $pendingLogs = TripLog::where('is_submitted', 1)->where('is_verified', 0)
            ->when($buId, function ($q) use ($buId) {
                return $q->whereHas('request', function ($q2) use ($buId) {
                    $this->applyBusinessUnitFilter($q2, $buId);
                });
            })->count();

        return [
            'total_fuel_cost' => $totalFuel,
            'total_service_cost' => $totalService,
            'total_operational_cost' => $totalFuel + $totalService,
            'total_distance' => $totalDistance,
            'pending_verification' => $pendingLogs,
        ];
    }

    private function getMonthlyChartData($buId)
    {
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('Y-m'));
        }

        $data = [];
        foreach ($months as $month) {
            [$year, $monthNum] = explode('-', $month);

            $fuelQuery = TripLog::where('is_verified', 1)
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum);
            if ($buId) {
                $fuelQuery->whereHas('request', function ($q) use ($buId) {
                    $this->applyBusinessUnitFilter($q, $buId);
                });
            }
            $fuel = $fuelQuery->sum('fuel_cost');

            $service = VehicleService::query()
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $monthNum);
            if ($buId) {
                $service->where('business_unit_id', $buId);
            }
            $service = $service->sum('cost');

            $data[] = [
                'month' => $month,
                'fuel' => $fuel,
                'service' => $service,
                'total' => $fuel + $service,
            ];
        }
        return $data;
    }

    private function getEfficiencyData($buId)
    {
        $query = TripLog::where('is_verified', 1)
            ->whereNotNull('odometer_start')
            ->whereNotNull('odometer_finish')
            ->whereNotNull('fuel_volume')
            ->where('fuel_volume', '>', 0);

        if ($buId) {
            $query->whereHas('request', function ($q) use ($buId) {
                $this->applyBusinessUnitFilter($q, $buId);
            });
        }

        $logs = $query->with('request.vehicle')->get();
        $grouped = $logs->groupBy(function ($log) {
            return $log->request->vehicle_id ?? 'unknown';
        });

        $result = [];
        foreach ($grouped as $vehicleId => $items) {
            $vehicle = $items->first()->request->vehicle;
            if (!$vehicle) continue;
            $avgEfficiency = $items->avg(function ($item) {
                return $item->efficiency;
            });
            $result[] = [
                'vehicle' => $vehicle->plate_number,
                'type' => $vehicle->type,
                'avg_efficiency' => round($avgEfficiency, 2),
                'total_trips' => $items->count(),
            ];
        }
        return collect($result)->sortByDesc('avg_efficiency')->take(10)->values();
    }

    /**
     * Distribusi transportasi berdasarkan request yang sudah disetujui admin
     * Menggunakan usage_date (tanggal perjalanan) sebagai filter periode
     */
    private function getTransportDistribution($buId, $month, $year)
    {
        $query = DriverRequest::whereIn('status', ['approved_admin', 'completed'])
            ->whereYear('usage_date', $year)
            ->whereMonth('usage_date', $month);

        if ($buId) {
            $this->applyBusinessUnitFilter($query, $buId);
        }

        return $query->select('transport_type', DB::raw('count(*) as total'))
            ->groupBy('transport_type')
            ->get();
    }

    private function getRecentLogs($buId, $limit = 5)
    {
        $query = TripLog::with(['request.requester', 'request.driver', 'request.vehicle'])
            ->where(function ($q) {
                $q->where('is_verified', 1)
                  ->orWhere('is_submitted', 1);
            })
            ->latest();

        if ($buId) {
            $query->whereHas('request', function ($q) use ($buId) {
                $this->applyBusinessUnitFilter($q, $buId);
            });
        }
        return $query->limit($limit)->get();
    }
}