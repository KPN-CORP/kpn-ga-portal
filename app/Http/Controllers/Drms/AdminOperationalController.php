<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\CalculatesOperationalStats;
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
    use CalculatesOperationalStats;

    /**
     * Menampilkan dashboard operasional dengan grafik dan statistik.
     */
    public function dashboard(Request $request)
    {
        $user = Auth::user();
        // Superadmin boleh memilih Business Unit lewat filter; admin biasa tetap terkunci ke BU-nya.
        $businessUnitId = $this->resolveBusinessUnitId($user, $request);
        // Filter periode: all (semua data) | year (1 tahun, DEFAULT = tahun berjalan) | month (1 bulan).
        ['period' => $period, 'year' => $year, 'month' => $month,
         'periodStart' => $periodStart, 'periodEnd' => $periodEnd, 'periodLabel' => $periodLabel] = $this->resolvePeriod($request);
        // Dipakai view: true kalau BUKAN satu bulan (Semua / Tahun)
        $isAllMonths = ($period !== 'month');
        // Tahun yang dipakai untuk filter query; null = semua tahun
        $yearFilter = ($period === 'all') ? null : $year;
        $filterVehicleId = $request->get('vehicle_id');
        $filterDriverId = $request->get('driver_id');

        // Trait CalculatesOperationalStats sekarang kerja pakai rentang tanggal
        // (date_from/date_to), bukan month/year — supaya bisa dipakai juga untuk
        // rentang bebas (API operational-summary). Dashboard web tetap filter
        // per bulan seperti biasa, cuma dikonversi ke rentang 1 bulan penuh
        // (atau 1 tahun penuh kalau "Semua Bulan" dipilih).
        // Dropdown filter
        $vehicles = Vehicle::when($businessUnitId, function ($q) use ($businessUnitId) {
            return $q->where('business_unit_id', $businessUnitId);
        })->orderBy('plate_number')->get();

        $drivers = Driver::when($businessUnitId, function ($q) use ($businessUnitId) {
            return $q->where('business_unit_id', $businessUnitId);
        })->orderBy('name')->get();

        // Daftar Business Unit untuk filter (dropdown aktif hanya untuk superadmin;
        // admin biasa melihat BU-nya sendiri, terkunci).
        $businessUnits = $user->isDrmsSuperAdmin()
            ? \App\Models\BisnisUnit::orderBy('nama_bisnis_unit')->get()
            : \App\Models\BisnisUnit::where('id_bisnis_unit', $businessUnitId)->get();
        $filterBusinessUnitId = $businessUnitId;

        // Kendaraan/driver yang dipilih tapi bukan bagian dari BU terpilih (mis. BU baru
        // diganti, kendaraan lama masih terbawa di URL) diabaikan supaya hasil tidak kosong.
        if ($filterVehicleId && !$vehicles->contains('id', (int) $filterVehicleId)) {
            $filterVehicleId = null;
        }
        if ($filterDriverId && !$drivers->contains('id', (int) $filterDriverId)) {
            $filterDriverId = null;
        }

        // Statistik
        $stats = $this->getOperationalStats($businessUnitId, $periodStart, $periodEnd, $filterVehicleId, $filterDriverId);
        $chartData = $this->getMonthlyChartData($businessUnitId, $filterVehicleId, $filterDriverId, $period, $year, $month);
        $efficiencyData = $this->getEfficiencyData($businessUnitId, $filterVehicleId, $filterDriverId);
        $transportDistribution = $this->getTransportDistribution($businessUnitId, $periodStart, $periodEnd, $filterVehicleId, $filterDriverId);
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
                ->when($month, fn($q) => $q->whereMonth('usage_date', $month))
                ->when($yearFilter, fn($q) => $q->whereYear('usage_date', $yearFilter))
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
                ->when($month, fn($q) => $q->whereMonth('filling_date', $month))
                ->when($yearFilter, fn($q) => $q->whereYear('filling_date', $yearFilter));
            if ($filterDriverId) $fuelQuery->where('driver_id', $filterDriverId);
            $fuelCost = $fuelQuery->sum('total_cost');

            // Service cost
            $serviceCost = ServiceSchedule::where('vehicle_id', $vehicle->id)
                ->when($month, fn($q) => $q->whereMonth('service_date', $month))
                ->when($yearFilter, fn($q) => $q->whereYear('service_date', $yearFilter))
                ->sum('cost');

            // Repair cost
            $repairCost = Repair::where('vehicle_id', $vehicle->id)
                ->when($month, fn($q) => $q->whereMonth('report_date', $month))
                ->when($yearFilter, fn($q) => $q->whereYear('report_date', $yearFilter))
                ->sum('total_cost');

            // Jarak & Liter/kWh — sekarang sumbernya Log Pengisian BBM/EV Charging
            // (FuelLog), BUKAN Log Perjalanan (TripLog) lagi. Jarak dihitung dari
            // selisih odometer_start antar pengisian yang berurutan dalam periode
            // ini, persis skema yang dipakai di FuelLogController::analytics(),
            // supaya "Rincian per Kendaraan" di dashboard konsisten dengan angka
            // yang ditampilkan di /drms/fuel-logs/analytics.
            $fuelLogStats = $this->getVehicleFuelLogStats($vehicle->id, $month, $yearFilter, $filterDriverId);
            $totalDistance = $fuelLogStats['distance'];
            $fuelLiters = $fuelLogStats['fuel_liters'];

            if ($fuelCost > 0 || $serviceCost > 0 || $repairCost > 0 || $totalDistance > 0) {
                $vehicleStats[] = [
                    'id'           => $vehicle->id, // dipakai FE untuk drill-down klik grafik ke halaman sumber data
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
        $years = $this->availableYears($businessUnitId, $year);

        $isSuperAdmin = $user->isDrmsSuperAdmin();
        // Nilai untuk pre-select <option> bulan di view: 'all' atau angka bulan.
        $monthSelect = $month;

        return view('drms.admin.operational_dashboard', compact(
            'stats', 'chartData', 'efficiencyData',
            'transportDistribution', 'months', 'years', 'month', 'year',
            'recentLogs', 'isSuperAdmin', 'isAllMonths', 'monthSelect', 'period', 'periodLabel',
            'vehicleStats', 'totals',
            'vehicles', 'drivers', 'filterVehicleId', 'filterDriverId',
            'businessUnits', 'filterBusinessUnitId'
        ));
    }

    /**
     * Export data ke CSV dengan detail lengkap (termasuk perbaikan).
     */
    public function export(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $this->resolveBusinessUnitId($user, $request);
        ['period' => $period, 'year' => $year, 'month' => $month,
         'periodStart' => $periodStart, 'periodEnd' => $periodEnd, 'periodLabel' => $periodLabel] = $this->resolvePeriod($request);
        $filterVehicleId = $request->get('vehicle_id');
        $filterDriverId = $request->get('driver_id');

        // Data statistik
        $stats = $this->getOperationalStats($businessUnitId, $periodStart, $periodEnd, $filterVehicleId, $filterDriverId);
        $efficiencyData = $this->getEfficiencyData($businessUnitId, $filterVehicleId, $filterDriverId);
        $transportDistribution = $this->getTransportDistribution($businessUnitId, $periodStart, $periodEnd, $filterVehicleId, $filterDriverId);

        // Detail logs
        $tripLogs = TripLog::with(['request.vehicle', 'request.driver', 'request'])
            ->where('is_verified', 1)
            ->whereHas('request', function ($q) use ($periodStart, $periodEnd, $filterVehicleId, $filterDriverId, $businessUnitId) {
                $q->whereBetween('usage_date', [$periodStart, $periodEnd]);
                if ($filterVehicleId) $q->where('vehicle_id', $filterVehicleId);
                if ($filterDriverId) $q->where('driver_id', $filterDriverId);
                if ($businessUnitId) $this->applyBusinessUnitFilter($q, $businessUnitId);
            })
            ->get();

        $fuelLogs = FuelLog::with(['vehicle', 'driver'])
            ->where('is_verified', 1)
            ->whereBetween('filling_date', [$periodStart, $periodEnd])
            ->when($businessUnitId, fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $businessUnitId)))
            ->when($filterVehicleId, fn($q) => $q->where('vehicle_id', $filterVehicleId))
            ->when($filterDriverId, fn($q) => $q->where('driver_id', $filterDriverId))
            ->get();

        $serviceSchedules = ServiceSchedule::with('vehicle')
            ->whereBetween('service_date', [$periodStart, $periodEnd])
            ->when($businessUnitId, fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $businessUnitId)))
            ->when($filterVehicleId, fn($q) => $q->where('vehicle_id', $filterVehicleId))
            ->get();

        $repairs = Repair::with('vehicle')
            ->whereBetween('report_date', [$periodStart, $periodEnd])
            ->when($businessUnitId, fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $businessUnitId)))
            ->when($filterVehicleId, fn($q) => $q->where('vehicle_id', $filterVehicleId))
            ->get();

        // Generate CSV
        $filename = 'laporan_operasional_' . ($period === 'all' ? 'semua' : ($period === 'year' ? $year : $year . '_' . str_pad($month, 2, '0', STR_PAD_LEFT))) . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $handle = fopen('php://output', 'w');

        // Header
        fputcsv($handle, ['LAPORAN OPERASIONAL DRMS']);
        fputcsv($handle, ['Periode: ' . $periodLabel]);
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
        $vehicleStats = $this->getVehicleStatsForPeriod($businessUnitId, $periodStart, $periodEnd, $filterVehicleId, $filterDriverId);
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

        // Pencarian: No. Request ATAU Plat Nomor + Merek kendaraan (nama driver tidak lagi
        // dicari lewat kolom ini — pakai dropdown Driver).
        if ($request->filled('search')) {
            $term = $request->search;
            $like = '%' . $term . '%';
            $query->whereHas('request', function ($q) use ($term, $like) {
                $q->where(function ($q1) use ($term, $like) {
                    $q1->where('request_no', 'LIKE', $like)
                       ->orWhereHas('vehicle', fn($q2) => $q2->searchPlateBrand($term));
                });
            });
        }

        // Filter Driver (dropdown)
        if ($request->filled('driver_id')) {
            $query->whereHas('request', fn($q) => $q->where('driver_id', $request->driver_id));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter Bulan (default: bulan sekarang), kecuali sudah pakai filter tanggal manual.
        // Pilih "Semua Bulan" (month=all) untuk menonaktifkan filter ini.
        $month = $request->get('month', now()->format('Y-m'));
        if (!$request->filled('date_from') && !$request->filled('date_to') && $month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [$year, $monthNum] = explode('-', $month);
            $query->whereYear('created_at', $year)->whereMonth('created_at', $monthNum);
        }

        // PENTING: hitung statistik dari QUERY PENUH (search/tanggal/bulan sudah masuk,
        // status BELUM difilter di sini) — supaya angkanya total keseluruhan yang cocok
        // dengan filter pencarian/tanggal/bulan, bukan cuma 20 data di halaman yang tampil.
        $totalLogs     = (clone $query)->count();
        $pendingCount  = (clone $query)->where('is_submitted', 1)->where('is_verified', 0)->count();
        $verifiedCount = (clone $query)->where('is_verified', 1)->count();
        $revisionCount = (clone $query)->where('is_submitted', 0)->where('is_verified', 0)->whereNotNull('revision_note')->count();
        $draftCount    = (clone $query)->where('is_submitted', 0)->where('is_verified', 0)->whereNull('revision_note')->count();

        // Filter status untuk daftar/tabel-nya diterapkan SETELAH statistik dihitung,
        // supaya statistik tetap menampilkan total keseluruhan walau lagi difilter status tertentu.
        if ($request->has('status')) {
            if ($request->status == 'pending') {
                $query->where('is_submitted', 1)->where('is_verified', 0);
            } elseif ($request->status == 'verified') {
                $query->where('is_verified', 1);
            } elseif ($request->status == 'draft') {
                $query->where('is_submitted', 0)->where('is_verified', 0)->whereNull('revision_note');
            } elseif ($request->status == 'revision') {
                $query->where('is_submitted', 0)->where('is_verified', 0)->whereNotNull('revision_note');
            }
        }

        $logs = $query->latest()->paginate(20)->appends($request->query());

        // Daftar driver untuk dropdown filter (dibatasi BU untuk admin biasa)
        $drivers = Driver::when($businessUnitId, fn($q) => $q->where('business_unit_id', $businessUnitId))
            ->orderBy('name')
            ->get(['id', 'name']);

        // Daftar kendaraan untuk rekomendasi (autocomplete) kotak pencarian Plat + Merek
        $vehicles = Vehicle::when($businessUnitId, fn($q) => $q->where('business_unit_id', $businessUnitId))
            ->orderBy('plate_number')
            ->get(['id', 'plate_number', 'type']);

        return view('drms.admin.monitoring_logs', compact(
            'logs', 'month', 'totalLogs', 'pendingCount', 'verifiedCount', 'draftCount', 'revisionCount', 'drivers', 'vehicles'
        ));
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

    /**
     * Business Unit yang dipakai untuk query dashboard/export.
     * - Superadmin: ikut filter business_unit_id di request (kosong = semua BU).
     * - Admin biasa: selalu BU miliknya sendiri (request diabaikan).
     */
    private function resolveBusinessUnitId($user, Request $request)
    {
        if ($user->isDrmsSuperAdmin()) {
            return $request->filled('business_unit_id') ? (int) $request->business_unit_id : null;
        }
        return $this->getBusinessUnitId($user);
    }

    private function getBusinessUnitId($user)
    {
        if ($user->isDrmsSuperAdmin()) return null;
        $profile = $user->drmsProfile;
        return $profile->business_unit_id ?? abort(403, 'Anda tidak memiliki unit bisnis.');
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

    /**
     * Tahun data paling awal (BBM/EV, service, perbaikan) untuk Business Unit tersebut.
     * Tidak ada data sama sekali -> tahun berjalan.
     */
    private function firstDataYear($buId = null): int
    {
        $byBu = fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $buId));
        $dates = array_filter([
            FuelLog::when($buId, $byBu)->min('filling_date'),
            ServiceSchedule::when($buId, $byBu)->min('service_date'),
            Repair::when($buId, $byBu)->min('report_date'),
        ]);
        return !empty($dates) ? (int) \Carbon\Carbon::parse(min($dates))->year : (int) now()->year;
    }

    /**
     * Pilihan tahun untuk dropdown filter: dari tahun data pertama s.d. tahun ini.
     * Tahun yang sedang dipilih (mis. dari URL) tetap dimasukkan supaya dropdown tidak janggal.
     */
    private function availableYears($buId = null, $selectedYear = null): array
    {
        $first = $this->firstDataYear($buId);
        $last = (int) now()->year;
        if ($selectedYear) {
            $first = min($first, (int) $selectedYear);
            $last = max($last, (int) $selectedYear);
        }
        return range($first, $last);
    }

    /**
     * Tentukan periode dari request: all | year | month.
     * DEFAULT (tanpa parameter) = tahun berjalan. URL lama dengan month=1..12 tetap = bulan,
     * month=all tetap = tahun.
     */
    private function resolvePeriod(Request $request): array
    {
        $monthParam = $request->get('month', now()->month);
        $period = $request->get('period');
        if (!in_array($period, ['all', 'year', 'month'], true)) {
            $period = $request->has('month')
                ? (($monthParam === 'all') ? 'year' : 'month')
                : 'year';
        }
        $year = (int) $request->get('year', now()->year);
        $month = null;
        if ($period === 'month') {
            $month = (is_numeric($monthParam) && $monthParam >= 1 && $monthParam <= 12)
                ? (int) $monthParam
                : (int) now()->month;
        }

        if ($period === 'all') {
            // Rentang sangat lebar supaya trait yang pakai date_from/date_to tetap bekerja.
            $periodStart = '2000-01-01';
            $periodEnd = '2100-12-31 23:59:59';
            $periodLabel = 'Semua Periode';
        } elseif ($period === 'year') {
            $periodStart = \Carbon\Carbon::create($year, 1, 1)->startOfYear()->toDateString();
            $periodEnd = \Carbon\Carbon::create($year, 12, 1)->endOfYear()->format('Y-m-d 23:59:59');
            $periodLabel = 'Tahun ' . $year;
        } else {
            $periodStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
            $periodEnd = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d 23:59:59');
            $periodLabel = date('F Y', mktime(0, 0, 0, $month, 1, $year));
        }

        return compact('period', 'year', 'month', 'periodStart', 'periodEnd', 'periodLabel');
    }

    /**
     * Data grafik biaya. Granularitas mengikuti periode:
     * - 'all'   : per TAHUN (dari tahun data paling awal s.d. tahun ini)
     * - 'year'  : per BULAN (Jan-Des pada $periodYear)
     * - 'month' : per HARI pada bulan $periodMonth / $periodYear
     * - null    : perilaku lama, 12 bulan terakhir dari hari ini
     * Key 'month' pada tiap baris berformat 'YYYY', 'YYYY-MM', atau 'YYYY-MM-DD'.
     */
    private function getMonthlyChartData($buId, $vehicleId = null, $driverId = null, $period = null, $periodYear = null, $periodMonth = null)
    {
        $labels = collect();
        $fmt = '%Y-%m';
        $from = null;
        $to = null;

        if ($period === 'all') {
            $startYear = $this->firstDataYear($buId);
            for ($y = $startYear; $y <= now()->year; $y++) {
                $labels->push((string) $y);
            }
            $fmt = '%Y';
            $from = $startYear . '-01-01';
        } elseif ($period === 'month' && $periodYear && $periodMonth) {
            $first = \Carbon\Carbon::create($periodYear, $periodMonth, 1);
            for ($d = 1; $d <= $first->daysInMonth; $d++) {
                $labels->push(sprintf('%04d-%02d-%02d', $periodYear, $periodMonth, $d));
            }
            $fmt = '%Y-%m-%d';
            $from = $first->toDateString();
            $to = $first->copy()->endOfMonth()->toDateString();
        } elseif ($period === 'year' && $periodYear) {
            for ($m = 1; $m <= 12; $m++) {
                $labels->push(sprintf('%04d-%02d', $periodYear, $m));
            }
            $from = $periodYear . '-01-01';
            $to = $periodYear . '-12-31';
        } else {
            // PENTING: anchor ke tanggal 1 (startOfMonth) SEBELUM subMonths(), supaya bulan
            // pendek (Feb/Apr/Jun/Sep/Nov) tidak "meluber" ke bulan berikutnya.
            $anchor = now()->startOfMonth();
            for ($i = 11; $i >= 0; $i--) {
                $labels->push($anchor->copy()->subMonths($i)->format('Y-m'));
            }
            $from = $labels->first() . '-01';
            $to = \Carbon\Carbon::parse($labels->last() . '-01')->endOfMonth()->toDateString();
        }

        // Satu query per jenis biaya, dikelompokkan sesuai granularitas (bukan 1 query per label).
        $grouped = function ($query, $dateCol, $sumCol) use ($fmt, $from, $to) {
            if ($from) $query->whereDate($dateCol, '>=', $from);
            if ($to) $query->whereDate($dateCol, '<=', $to);
            return $query
                ->selectRaw("DATE_FORMAT($dateCol, '$fmt') as k, SUM($sumCol) as t")
                ->groupBy('k')
                ->pluck('t', 'k');
        };

        $fuelQuery = FuelLog::where('is_verified', 1);
        if ($buId) $fuelQuery->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
        if ($vehicleId) $fuelQuery->where('vehicle_id', $vehicleId);
        if ($driverId) $fuelQuery->where('driver_id', $driverId);
        $fuelMap = $grouped($fuelQuery, 'filling_date', 'total_cost');

        $serviceQuery = ServiceSchedule::query();
        if ($buId) $serviceQuery->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
        if ($vehicleId) $serviceQuery->where('vehicle_id', $vehicleId);
        $serviceMap = $grouped($serviceQuery, 'service_date', 'cost');

        $repairQuery = Repair::query();
        if ($buId) $repairQuery->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
        if ($vehicleId) $repairQuery->where('vehicle_id', $vehicleId);
        $repairMap = $grouped($repairQuery, 'report_date', 'total_cost');

        $data = [];
        foreach ($labels as $label) {
            $fuel = (float) ($fuelMap[$label] ?? 0);
            $service = (float) ($serviceMap[$label] ?? 0);
            $repair = (float) ($repairMap[$label] ?? 0);
            $data[] = [
                'month'   => $label,
                'fuel'    => $fuel,
                'service' => $service,
                'repair'  => $repair,
                'total'   => $fuel + $service + $repair,
            ];
        }
        return $data;
    }

    /**
     * Hitung jarak & total liter/kWh satu kendaraan untuk periode (bulan/tahun) tertentu,
     * bersumber dari Log Pengisian BBM/EV Charging (FuelLog) yang sudah diverifikasi.
     * Jarak = akumulasi selisih odometer_start antar pengisian berurutan (skema yang
     * sama seperti FuelLogController::analytics), bukan dari Log Perjalanan (TripLog).
     */
    /**
     * $month bisa null (artinya "Semua Bulan" — hanya difilter per tahun).
     */
    private function getVehicleFuelLogStats($vehicleId, $month, $year, $filterDriverId = null)
    {
        $query = FuelLog::where('vehicle_id', $vehicleId)
            ->where('is_verified', 1)
            ->when($month, fn($q) => $q->whereMonth('filling_date', $month))
            ->when($year, fn($q) => $q->whereYear('filling_date', $year));
        if ($filterDriverId) $query->where('driver_id', $filterDriverId);

        $items = $query->orderBy('filling_date')->orderBy('odometer_start')->get();

        $fuelLiters = $items->sum('fuel_liters');

        $totalDistance = 0;
        $prevOdometer = null;
        foreach ($items as $item) {
            if ($prevOdometer !== null && $item->odometer_start > $prevOdometer) {
                $totalDistance += ($item->odometer_start - $prevOdometer);
            }
            $prevOdometer = $item->odometer_start;
        }

        return [
            'distance'    => $totalDistance,
            'fuel_liters' => $fuelLiters,
        ];
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
}