<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\TripLog;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\Vehicle;
use App\Models\Drms\Driver;
use App\Models\Drms\FuelLog;
use App\Models\Drms\ServiceSchedule;
use App\Models\Drms\Repair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminOperationalController extends Controller
{
    /**
     * Menampilkan dashboard operasional dengan grafik dan statistik.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $this->getBusinessUnitId($user);
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $filterVehicleId = $request->get('vehicle_id');
        $filterDriverId = $request->get('driver_id');

        // Dropdown filter
        $vehicles = Vehicle::when($businessUnitId, function ($q) use ($businessUnitId) {
            return $q->where('business_unit_id', $businessUnitId);
        })->orderBy('plate_number')->get();

        $drivers = Driver::when($businessUnitId, function ($q) use ($businessUnitId) {
            return $q->where('business_unit_id', $businessUnitId);
        })->orderBy('name')->get();

        // Statistik
        $stats = $this->getOperationalStats($businessUnitId, $month, $year, $filterVehicleId, $filterDriverId);
        $chartData = $this->getMonthlyChartData($businessUnitId, $filterVehicleId, $filterDriverId);
        $efficiencyData = $this->getEfficiencyData($businessUnitId, $filterVehicleId, $filterDriverId);
        $transportDistribution = $this->getTransportDistribution($businessUnitId, $month, $year, $filterVehicleId, $filterDriverId);
        $recentLogs = $this->getRecentLogs($businessUnitId, 5, $filterVehicleId, $filterDriverId);

        // Data per kendaraan
        $vehicleStats = [];
        $filteredVehicles = $vehicles;

        if ($filterVehicleId) {
            $filteredVehicles = $vehicles->where('id', $filterVehicleId);
        }

        if ($filterDriverId) {
            $driverVehicleIds = DriverRequest::where('driver_id', $filterDriverId)
                ->whereIn('status', ['approved_admin', 'completed'])
                ->whereMonth('usage_date', $month)
                ->whereYear('usage_date', $year)
                ->pluck('vehicle_id')
                ->unique()
                ->toArray();
            if (!empty($driverVehicleIds)) {
                $filteredVehicles = $vehicles->whereIn('id', $driverVehicleIds);
            } else {
                $filteredVehicles = collect();
            }
        }

        foreach ($filteredVehicles as $vehicle) {
            // Fuel cost
            $fuelQuery = FuelLog::where('vehicle_id', $vehicle->id)
                ->where('is_verified', 1)
                ->whereMonth('filling_date', $month)
                ->whereYear('filling_date', $year);
            if ($filterDriverId) $fuelQuery->where('driver_id', $filterDriverId);
            $fuelCost = $fuelQuery->sum(DB::raw('fuel_liters * fuel_price_per_liter'));

            // Service cost
            $serviceCost = ServiceSchedule::where('vehicle_id', $vehicle->id)
                ->whereMonth('service_date', $month)
                ->whereYear('service_date', $year)
                ->sum('cost');

            // Repair cost
            $repairCost = Repair::where('vehicle_id', $vehicle->id)
                ->whereMonth('report_date', $month)
                ->whereYear('report_date', $year)
                ->sum('total_cost');

            // Distance
            $fuelLogs = FuelLog::where('vehicle_id', $vehicle->id)
                ->where('is_verified', 1)
                ->whereMonth('filling_date', $month)
                ->whereYear('filling_date', $year);
            if ($filterDriverId) $fuelLogs->where('driver_id', $filterDriverId);
            $fuelLogs = $fuelLogs->orderBy('filling_date')->get(['odometer_start']);
            $totalDistance = 0;
            if ($fuelLogs->count() > 1) {
                $prev = null;
                foreach ($fuelLogs as $log) {
                    if ($prev !== null && $log->odometer_start > $prev) {
                        $totalDistance += ($log->odometer_start - $prev);
                    }
                    $prev = $log->odometer_start;
                }
            }

            // Fuel liters
            $fuelLiters = FuelLog::where('vehicle_id', $vehicle->id)
                ->where('is_verified', 1)
                ->whereMonth('filling_date', $month)
                ->whereYear('filling_date', $year);
            if ($filterDriverId) $fuelLiters->where('driver_id', $filterDriverId);
            $fuelLiters = $fuelLiters->sum('fuel_liters');

            if ($fuelCost > 0 || $serviceCost > 0 || $repairCost > 0 || $totalDistance > 0) {
                $vehicleStats[] = [
                    'plate_number' => $vehicle->plate_number,
                    'fuel_cost'    => $fuelCost,
                    'service_cost' => $serviceCost,
                    'repair_cost'  => $repairCost,
                    'total_cost'   => $fuelCost + $serviceCost + $repairCost,
                    'distance'     => $totalDistance,
                    'fuel_liters'  => $fuelLiters,
                ];
            }
        }

        usort($vehicleStats, function ($a, $b) {
            return $b['total_cost'] <=> $a['total_cost'];
        });

        $totals = [
            'total_operational_cost' => array_sum(array_column($vehicleStats, 'total_cost')),
            'total_fuel_cost' => array_sum(array_column($vehicleStats, 'fuel_cost')),
            'total_service_cost' => array_sum(array_column($vehicleStats, 'service_cost')),
            'total_repair_cost' => array_sum(array_column($vehicleStats, 'repair_cost')),
            'total_distance' => array_sum(array_column($vehicleStats, 'distance')),
            'total_fuel_liters' => array_sum(array_column($vehicleStats, 'fuel_liters')),
        ];

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[$i] = date('F', mktime(0, 0, 0, $i, 1));
        }
        $years = range(now()->year - 2, now()->year);

        $isSuperAdmin = $user->isDrmsSuperAdmin();

        return view('drms.admin.operational_dashboard', compact(
            'stats', 'chartData', 'efficiencyData',
            'transportDistribution', 'months', 'years', 'month', 'year',
            'recentLogs', 'isSuperAdmin',
            'vehicleStats', 'totals',
            'vehicles', 'drivers', 'filterVehicleId', 'filterDriverId'
        ));
    }

    /**
     * Export data ke CSV dengan detail lengkap (termasuk perbaikan).
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $this->getBusinessUnitId($user);
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $filterVehicleId = $request->get('vehicle_id');
        $filterDriverId = $request->get('driver_id');

        // Data statistik
        $stats = $this->getOperationalStats($businessUnitId, $month, $year, $filterVehicleId, $filterDriverId);
        $efficiencyData = $this->getEfficiencyData($businessUnitId, $filterVehicleId, $filterDriverId);
        $transportDistribution = $this->getTransportDistribution($businessUnitId, $month, $year, $filterVehicleId, $filterDriverId);

        // Detail logs
        $tripLogs = TripLog::with(['request.vehicle', 'request.driver', 'request'])
            ->where('is_verified', 1)
            ->whereHas('request', function ($q) use ($month, $year, $filterVehicleId, $filterDriverId, $businessUnitId) {
                $q->whereYear('usage_date', $year)
                  ->whereMonth('usage_date', $month);
                if ($filterVehicleId) $q->where('vehicle_id', $filterVehicleId);
                if ($filterDriverId) $q->where('driver_id', $filterDriverId);
                if ($businessUnitId) $this->applyBusinessUnitFilter($q, $businessUnitId);
            })
            ->get();

        $fuelLogs = FuelLog::with(['vehicle', 'driver'])
            ->where('is_verified', 1)
            ->whereMonth('filling_date', $month)
            ->whereYear('filling_date', $year)
            ->when($businessUnitId, fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $businessUnitId)))
            ->when($filterVehicleId, fn($q) => $q->where('vehicle_id', $filterVehicleId))
            ->when($filterDriverId, fn($q) => $q->where('driver_id', $filterDriverId))
            ->get();

        $serviceSchedules = ServiceSchedule::with('vehicle')
            ->whereMonth('service_date', $month)
            ->whereYear('service_date', $year)
            ->when($businessUnitId, fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $businessUnitId)))
            ->when($filterVehicleId, fn($q) => $q->where('vehicle_id', $filterVehicleId))
            ->get();

        $repairs = Repair::with('vehicle')
            ->whereMonth('report_date', $month)
            ->whereYear('report_date', $year)
            ->when($businessUnitId, fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $businessUnitId)))
            ->when($filterVehicleId, fn($q) => $q->where('vehicle_id', $filterVehicleId))
            ->get();

        // Generate CSV
        $filename = 'laporan_operasional_' . $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT) . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $handle = fopen('php://output', 'w');

        // Header
        fputcsv($handle, ['LAPORAN OPERASIONAL DRMS']);
        fputcsv($handle, ['Periode: ' . date('F Y', mktime(0, 0, 0, $month, 1, $year))]);
        if ($businessUnitId) {
            $buName = \App\Models\BisnisUnit::find($businessUnitId)->nama_bisnis_unit ?? 'Unit';
            fputcsv($handle, ['Business Unit: ' . $buName]);
        } else {
            fputcsv($handle, ['Business Unit: SEMUA (Superadmin)']);
        }
        if ($filterVehicleId) {
            $vehicle = Vehicle::find($filterVehicleId);
            fputcsv($handle, ['Kendaraan: ' . ($vehicle->plate_number ?? '-')]);
        }
        if ($filterDriverId) {
            $driver = Driver::find($filterDriverId);
            fputcsv($handle, ['Driver: ' . ($driver->name ?? '-')]);
        }
        fputcsv($handle, []);
        fputcsv($handle, ['Tanggal Export: ' . now()->format('d-m-Y H:i:s')]);
        fputcsv($handle, []);

        // Ringkasan
        fputcsv($handle, ['RINGKASAN']);
        fputcsv($handle, ['Total Biaya Operasi', 'Rp ' . number_format($stats['total_operational_cost'], 0, ',', '.')]);
        fputcsv($handle, ['Total BBM/Charge', 'Rp ' . number_format($stats['total_fuel_cost'], 0, ',', '.')]);
        fputcsv($handle, ['Total Service', 'Rp ' . number_format($stats['total_service_cost'], 0, ',', '.')]);
        fputcsv($handle, ['Total Perbaikan', 'Rp ' . number_format($stats['total_repair_cost'] ?? 0, 0, ',', '.')]);
        fputcsv($handle, ['Total Jarak Tempuh', number_format($stats['total_distance'], 0, ',', '.') . ' km']);
        fputcsv($handle, ['Menunggu Verifikasi', $stats['pending_verification'] . ' log']);
        fputcsv($handle, []);

        // Distribusi Transportasi
        fputcsv($handle, ['DISTRIBUSI TRANSPORTASI']);
        fputcsv($handle, ['Tipe Transportasi', 'Jumlah']);
        foreach ($transportDistribution as $item) {
            fputcsv($handle, [
                $item->transport_type ? ucfirst(str_replace('_', ' ', $item->transport_type)) : 'Tidak Diketahui',
                $item->total
            ]);
        }
        fputcsv($handle, []);

        // Efisiensi
        fputcsv($handle, ['EFISIENSI KENDARAAN (TOP 10)']);
        fputcsv($handle, ['Kendaraan', 'Rata-rata Efisiensi (L/100km)', 'Total Perjalanan']);
        foreach ($efficiencyData as $item) {
            fputcsv($handle, [
                $item['vehicle'],
                $item['avg_efficiency'],
                $item['total_trips']
            ]);
        }
        fputcsv($handle, []);

        // Rincian per Kendaraan
        $vehicleStats = $this->getVehicleStatsForPeriod($businessUnitId, $month, $year, $filterVehicleId, $filterDriverId);
        fputcsv($handle, ['RINCIAN PER KENDARAAN']);
        fputcsv($handle, ['Kendaraan', 'BBM/Charge (Rp)', 'Service (Rp)', 'Perbaikan (Rp)', 'Total Biaya (Rp)', 'Jarak (km)', 'Liter/kWh']);
        foreach ($vehicleStats as $v) {
            fputcsv($handle, [
                $v['plate_number'],
                number_format($v['fuel_cost'], 0, ',', '.'),
                number_format($v['service_cost'], 0, ',', '.'),
                number_format($v['repair_cost'] ?? 0, 0, ',', '.'),
                number_format($v['total_cost'], 0, ',', '.'),
                number_format($v['distance'], 0, ',', '.'),
                number_format($v['fuel_liters'], 2, ',', '.')
            ]);
        }
        fputcsv($handle, []);

        // Trip Logs
        fputcsv($handle, ['TRIP LOGS (Terverifikasi)']);
        fputcsv($handle, ['Request', 'Driver', 'Kendaraan', 'Tanggal', 'Jarak (km)', 'BBM (L/kWh)', 'Biaya BBM (Rp)']);
        foreach ($tripLogs as $log) {
            fputcsv($handle, [
                $log->request->request_no ?? '-',
                $log->request->driver->name ?? '-',
                $log->request->vehicle->plate_number ?? '-',
                $log->request->usage_date ?? '-',
                ($log->odometer_finish - $log->odometer_start) ?? 0,
                $log->fuel_volume ?? 0,
                $log->fuel_cost ?? 0,
            ]);
        }
        fputcsv($handle, []);

        // Fuel Logs
        fputcsv($handle, ['FUEL LOGS (Pengisian BBM)']);
        fputcsv($handle, ['Kendaraan', 'Driver', 'Tanggal Isi', 'Odometer', 'Liter', 'Harga/Liter', 'Total Biaya']);
        foreach ($fuelLogs as $log) {
            fputcsv($handle, [
                $log->vehicle->plate_number ?? '-',
                $log->driver->name ?? '-',
                $log->filling_date,
                $log->odometer_start,
                $log->fuel_liters,
                $log->fuel_price_per_liter,
                $log->total_cost,
            ]);
        }
        fputcsv($handle, []);

        // Service Schedules
        fputcsv($handle, ['SERVICE SCHEDULES']);
        fputcsv($handle, ['Kendaraan', 'Tanggal Servis', 'Jenis Servis', 'Biaya (Rp)', 'Bengkel']);
        foreach ($serviceSchedules as $s) {
            fputcsv($handle, [
                $s->vehicle->plate_number ?? '-',
                $s->service_date,
                str_replace('_', ' ', ucfirst($s->service_type)),
                $s->cost,
                $s->workshop_name ?? '-',
            ]);
        }
        fputcsv($handle, []);

        // Repairs
        fputcsv($handle, ['REPAIRS (Perbaikan)']);
        fputcsv($handle, ['Kendaraan', 'Tanggal Laporan', 'Status', 'Total Biaya (Rp)', 'Keluhan']);
        foreach ($repairs as $r) {
            fputcsv($handle, [
                $r->vehicle->plate_number ?? '-',
                $r->report_date,
                ucfirst($r->status),
                $r->total_cost,
                $r->complaint,
            ]);
        }
        fputcsv($handle, []);

        fclose($handle);
        exit;
    }

    /**
     * Halaman monitoring log driver.
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

    /**
     * Form verifikasi log.
     */
    public function verifyLogForm($logId)
    {
        $log = TripLog::with(['request.requester', 'request.driver', 'request.vehicle'])
            ->findOrFail($logId);
        $this->authorizeLogAccess($log);
        return view('drms.admin.verify_log', compact('log'));
    }

    /**
     * Proses verifikasi log.
     */
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

    // ==================== HELPER METHODS ====================

    private function getBusinessUnitId($user)
    {
        if ($user->isDrmsSuperAdmin()) return null;
        $profile = $user->drmsProfile;
        return $profile->business_unit_id ?? abort(403, 'Anda tidak memiliki unit bisnis.');
    }

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

    private function getOperationalStats($buId, $month, $year, $vehicleId = null, $driverId = null)
    {
        // Fuel
        $fuelQuery = FuelLog::where('is_verified', 1)
            ->whereMonth('filling_date', $month)
            ->whereYear('filling_date', $year);
        if ($buId) $fuelQuery->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
        if ($vehicleId) $fuelQuery->where('vehicle_id', $vehicleId);
        if ($driverId) $fuelQuery->where('driver_id', $driverId);
        $totalFuel = $fuelQuery->sum(DB::raw('fuel_liters * fuel_price_per_liter'));

        // Service
        $serviceQuery = ServiceSchedule::whereMonth('service_date', $month)->whereYear('service_date', $year);
        if ($buId) $serviceQuery->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
        if ($vehicleId) $serviceQuery->where('vehicle_id', $vehicleId);
        $totalService = $serviceQuery->sum('cost');

        // Repair
        $repairQuery = Repair::whereMonth('report_date', $month)->whereYear('report_date', $year);
        if ($buId) $repairQuery->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
        if ($vehicleId) $repairQuery->where('vehicle_id', $vehicleId);
        $totalRepair = $repairQuery->sum('total_cost');

        // Pending
        $pendingLogs = TripLog::where('is_submitted', 1)->where('is_verified', 0)
            ->when($buId, fn($q) => $q->whereHas('request', fn($q2) => $this->applyBusinessUnitFilter($q2, $buId)))
            ->when($vehicleId, fn($q) => $q->whereHas('request', fn($q2) => $q2->where('vehicle_id', $vehicleId)))
            ->when($driverId, fn($q) => $q->whereHas('request', fn($q2) => $q2->where('driver_id', $driverId)))
            ->count();

        // Distance
        $fuelLogs = FuelLog::where('is_verified', 1)
            ->whereMonth('filling_date', $month)
            ->whereYear('filling_date', $year)
            ->when($buId, fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $buId)))
            ->when($vehicleId, fn($q) => $q->where('vehicle_id', $vehicleId))
            ->when($driverId, fn($q) => $q->where('driver_id', $driverId))
            ->orderBy('vehicle_id')->orderBy('filling_date')
            ->get(['vehicle_id', 'odometer_start']);

        $totalDistance = 0;
        if ($fuelLogs->isNotEmpty()) {
            $grouped = $fuelLogs->groupBy('vehicle_id');
            foreach ($grouped as $logs) {
                if ($logs->count() > 1) {
                    $prev = null;
                    foreach ($logs as $log) {
                        if ($prev !== null && $log->odometer_start > $prev) {
                            $totalDistance += ($log->odometer_start - $prev);
                        }
                        $prev = $log->odometer_start;
                    }
                }
            }
        }

        return [
            'total_fuel_cost'      => $totalFuel,
            'total_service_cost'   => $totalService,
            'total_repair_cost'    => $totalRepair,
            'total_operational_cost' => $totalFuel + $totalService + $totalRepair,
            'total_distance'       => $totalDistance,
            'pending_verification' => $pendingLogs,
        ];
    }

    private function getMonthlyChartData($buId, $vehicleId = null, $driverId = null)
    {
        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('Y-m'));
        }

        $data = [];
        foreach ($months as $month) {
            [$year, $monthNum] = explode('-', $month);

            $fuelQuery = FuelLog::where('is_verified', 1)
                ->whereYear('filling_date', $year)
                ->whereMonth('filling_date', $monthNum);
            if ($buId) $fuelQuery->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
            if ($vehicleId) $fuelQuery->where('vehicle_id', $vehicleId);
            if ($driverId) $fuelQuery->where('driver_id', $driverId);
            $fuel = $fuelQuery->sum(DB::raw('fuel_liters * fuel_price_per_liter'));

            $serviceQuery = ServiceSchedule::whereYear('service_date', $year)->whereMonth('service_date', $monthNum);
            if ($buId) $serviceQuery->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
            if ($vehicleId) $serviceQuery->where('vehicle_id', $vehicleId);
            $service = $serviceQuery->sum('cost');

            $repairQuery = Repair::whereYear('report_date', $year)->whereMonth('report_date', $monthNum);
            if ($buId) $repairQuery->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
            if ($vehicleId) $repairQuery->where('vehicle_id', $vehicleId);
            $repair = $repairQuery->sum('total_cost');

            $data[] = [
                'month'   => $month,
                'fuel'    => $fuel,
                'service' => $service,
                'repair'  => $repair,
                'total'   => $fuel + $service + $repair,
            ];
        }
        return $data;
    }

    private function getEfficiencyData($buId, $vehicleId = null, $driverId = null)
    {
        $fuelLogs = FuelLog::with('vehicle')
            ->where('is_verified', 1)
            ->when($buId, fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $buId)))
            ->when($vehicleId, fn($q) => $q->where('vehicle_id', $vehicleId))
            ->when($driverId, fn($q) => $q->where('driver_id', $driverId))
            ->orderBy('vehicle_id')->orderBy('filling_date')
            ->get();

        $grouped = $fuelLogs->groupBy('vehicle_id');
        $result = [];
        foreach ($grouped as $items) {
            $vehicle = $items->first()->vehicle;
            if (!$vehicle) continue;
            $totalDistance = 0;
            $prevOdometer = null;
            $totalLiters = 0;
            foreach ($items as $item) {
                if ($prevOdometer !== null && $item->odometer_start > $prevOdometer) {
                    $totalDistance += ($item->odometer_start - $prevOdometer);
                }
                $prevOdometer = $item->odometer_start;
                $totalLiters += $item->fuel_liters;
            }
            $avgConsumption = ($totalDistance > 0) ? round(($totalLiters / $totalDistance) * 100, 2) : null;
            if ($avgConsumption !== null) {
                $result[] = [
                    'vehicle'      => $vehicle->plate_number,
                    'type'         => $vehicle->type,
                    'avg_efficiency' => $avgConsumption,
                    'total_trips'  => $items->count(),
                ];
            }
        }
        return collect($result)->sortBy('avg_efficiency')->take(10)->values();
    }

    private function getTransportDistribution($buId, $month, $year, $vehicleId = null, $driverId = null)
    {
        $query = DriverRequest::whereIn('status', ['approved_admin', 'completed'])
            ->whereYear('usage_date', $year)
            ->whereMonth('usage_date', $month);
        if ($buId) $this->applyBusinessUnitFilter($query, $buId);
        if ($vehicleId) $query->where('vehicle_id', $vehicleId);
        if ($driverId) $query->where('driver_id', $driverId);
        return $query->select('transport_type', DB::raw('count(*) as total'))
            ->groupBy('transport_type')
            ->get();
    }

    private function getRecentLogs($buId, $limit = 5, $vehicleId = null, $driverId = null)
    {
        $query = TripLog::with(['request.requester', 'request.driver', 'request.vehicle'])
            ->where(function ($q) {
                $q->where('is_verified', 1)->orWhere('is_submitted', 1);
            })
            ->latest();
        if ($buId) {
            $query->whereHas('request', fn($q) => $this->applyBusinessUnitFilter($q, $buId));
        }
        if ($vehicleId) $query->whereHas('request', fn($q) => $q->where('vehicle_id', $vehicleId));
        if ($driverId) $query->whereHas('request', fn($q) => $q->where('driver_id', $driverId));
        return $query->limit($limit)->get();
    }

    private function getVehicleStatsForPeriod($buId, $month, $year, $vehicleId = null, $driverId = null)
    {
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))
            ->when($vehicleId, fn($q) => $q->where('id', $vehicleId))
            ->get();

        $stats = [];
        foreach ($vehicles as $vehicle) {
            $fuelQuery = FuelLog::where('vehicle_id', $vehicle->id)
                ->where('is_verified', 1)
                ->whereMonth('filling_date', $month)
                ->whereYear('filling_date', $year);
            if ($driverId) $fuelQuery->where('driver_id', $driverId);
            $fuelCost = $fuelQuery->sum(DB::raw('fuel_liters * fuel_price_per_liter'));

            $serviceCost = ServiceSchedule::where('vehicle_id', $vehicle->id)
                ->whereMonth('service_date', $month)
                ->whereYear('service_date', $year)
                ->sum('cost');

            $repairCost = Repair::where('vehicle_id', $vehicle->id)
                ->whereMonth('report_date', $month)
                ->whereYear('report_date', $year)
                ->sum('total_cost');

            $fuelLogs = FuelLog::where('vehicle_id', $vehicle->id)
                ->where('is_verified', 1)
                ->whereMonth('filling_date', $month)
                ->whereYear('filling_date', $year);
            if ($driverId) $fuelLogs->where('driver_id', $driverId);
            $fuelLogs = $fuelLogs->orderBy('filling_date')->get(['odometer_start']);
            $totalDistance = 0;
            if ($fuelLogs->count() > 1) {
                $prev = null;
                foreach ($fuelLogs as $log) {
                    if ($prev !== null && $log->odometer_start > $prev) {
                        $totalDistance += ($log->odometer_start - $prev);
                    }
                    $prev = $log->odometer_start;
                }
            }

            $fuelLiters = FuelLog::where('vehicle_id', $vehicle->id)
                ->where('is_verified', 1)
                ->whereMonth('filling_date', $month)
                ->whereYear('filling_date', $year);
            if ($driverId) $fuelLiters->where('driver_id', $driverId);
            $fuelLiters = $fuelLiters->sum('fuel_liters');

            if ($fuelCost > 0 || $serviceCost > 0 || $repairCost > 0 || $totalDistance > 0) {
                $stats[] = [
                    'plate_number' => $vehicle->plate_number,
                    'fuel_cost'    => $fuelCost,
                    'service_cost' => $serviceCost,
                    'repair_cost'  => $repairCost,
                    'total_cost'   => $fuelCost + $serviceCost + $repairCost,
                    'distance'     => $totalDistance,
                    'fuel_liters'  => $fuelLiters,
                ];
            }
        }
        return $stats;
    }
}