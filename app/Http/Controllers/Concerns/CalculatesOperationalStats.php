<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Drms\FuelLog;
use App\Models\Drms\Repair;
use App\Models\Drms\ServiceSchedule;
use App\Models\Drms\TripLog;
use App\Models\Drms\DriverRequest;
use App\Models\Drms\Vehicle;
use Illuminate\Support\Facades\DB;

/**
 * Logic perhitungan ringkasan operasional (biaya, distribusi transportasi,
 * efisiensi, rincian per kendaraan).
 *
 * SENGAJA dipisah jadi trait supaya dipakai bareng oleh:
 * - AdminOperationalController (dashboard web & Export CSV)
 * - Api\OperationalReportController (endpoint /api/v1/operational-summary)
 *
 * Kalau logic perhitungan berubah, cukup ubah di sini — otomatis konsisten
 * di CSV export maupun API, gak perlu maintain 2 tempat terpisah.
 */
trait CalculatesOperationalStats
{
    /**
     * Kelompokkan kendaraan jadi 2: "listrik" (fuel_type = Listrik persis) vs
     * "bbm" (semua SELAIN Listrik — Bensin/Solar/Hybrid/Lainnya/kosong, digabung
     * jadi satu). Ini SENGAJA disamain persis dengan logic yang udah ada di
     * FuelLogController::index() (pemisah kelompok BBM vs Listrik di analytics),
     * termasuk aturan fuel_type NULL dianggap BBM (bukan dikecualikan).
     *
     * $onVehicleTable = true  → query-nya langsung ke tabel Vehicle (kolom fuel_type ada di situ)
     * $onVehicleTable = false → query-nya ke tabel lain yang punya relasi 'vehicle' (pakai whereHas)
     */
    private function applyFuelGroupFilter($query, $fuelGroup, $onVehicleTable = false)
    {
        if (!$fuelGroup) return $query;

        $apply = function ($q) use ($fuelGroup) {
            if (strtolower($fuelGroup) === 'listrik') {
                $q->where('fuel_type', 'Listrik');
            } else { // 'bbm' (atau nilai lain di luar 'listrik') = semua selain Listrik, termasuk NULL
                $q->where('fuel_type', '!=', 'Listrik')->orWhereNull('fuel_type');
            }
        };

        return $onVehicleTable
            ? $query->where($apply)
            : $query->whereHas('vehicle', $apply);
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

    private function getOperationalStats($buId, $dateFrom, $dateTo, $vehicleId = null, $driverId = null)
    {
        $fuelQuery = FuelLog::where('is_verified', 1)
            ->whereBetween('filling_date', [$dateFrom, $dateTo]);
        if ($buId) $fuelQuery->whereHas('vehicle', fn ($q) => $q->where('business_unit_id', $buId));
        if ($vehicleId) $fuelQuery->where('vehicle_id', $vehicleId);
        if ($driverId) $fuelQuery->where('driver_id', $driverId);
        $totalFuel = $fuelQuery->sum(DB::raw('fuel_liters * fuel_price_per_liter'));

        $serviceQuery = ServiceSchedule::whereBetween('service_date', [$dateFrom, $dateTo]);
        if ($buId) $serviceQuery->whereHas('vehicle', fn ($q) => $q->where('business_unit_id', $buId));
        if ($vehicleId) $serviceQuery->where('vehicle_id', $vehicleId);
        $totalService = $serviceQuery->sum('cost');

        $repairQuery = Repair::whereBetween('report_date', [$dateFrom, $dateTo]);
        if ($buId) $repairQuery->whereHas('vehicle', fn ($q) => $q->where('business_unit_id', $buId));
        if ($vehicleId) $repairQuery->where('vehicle_id', $vehicleId);
        $totalRepair = $repairQuery->sum('total_cost');

        $pendingLogs = TripLog::where('is_submitted', 1)->where('is_verified', 0)
            ->when($buId, fn ($q) => $q->whereHas('request', fn ($q2) => $this->applyBusinessUnitFilter($q2, $buId)))
            ->when($vehicleId, fn ($q) => $q->whereHas('request', fn ($q2) => $q2->where('vehicle_id', $vehicleId)))
            ->when($driverId, fn ($q) => $q->whereHas('request', fn ($q2) => $q2->where('driver_id', $driverId)))
            ->count();

        $totalDistance = TripLog::where('is_verified', 1)
            ->whereNotNull('odometer_start')
            ->whereNotNull('odometer_finish')
            ->whereHas('request', function ($q) use ($buId, $vehicleId, $driverId, $dateFrom, $dateTo) {
                $q->whereBetween('usage_date', [$dateFrom, $dateTo]);
                if ($buId) $this->applyBusinessUnitFilter($q, $buId);
                if ($vehicleId) $q->where('vehicle_id', $vehicleId);
                if ($driverId) $q->where('driver_id', $driverId);
            })
            ->get()
            ->sum(fn ($log) => max(0, $log->odometer_finish - $log->odometer_start));

        $totalTrips = TripLog::where('is_verified', 1)
            ->whereHas('request', function ($q) use ($buId, $vehicleId, $driverId, $dateFrom, $dateTo) {
                $q->whereBetween('usage_date', [$dateFrom, $dateTo]);
                if ($buId) $this->applyBusinessUnitFilter($q, $buId);
                if ($vehicleId) $q->where('vehicle_id', $vehicleId);
                if ($driverId) $q->where('driver_id', $driverId);
            })
            ->count();

        $effFuelLogs = FuelLog::where('is_verified', 1)
            ->whereBetween('filling_date', [$dateFrom, $dateTo])
            ->when($buId, fn ($q) => $q->whereHas('vehicle', fn ($sq) => $sq->where('business_unit_id', $buId)))
            ->when($vehicleId, fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->when($driverId, fn ($q) => $q->where('driver_id', $driverId))
            ->orderBy('vehicle_id')->orderBy('filling_date')
            ->get(['vehicle_id', 'odometer_start', 'fuel_liters']);

        $avgEfficiency = null;
        if ($effFuelLogs->isNotEmpty()) {
            $totalEffDistance = 0;
            $totalEffLiters = 0;
            foreach ($effFuelLogs->groupBy('vehicle_id') as $items) {
                $prevOdometer = null;
                foreach ($items as $item) {
                    if ($prevOdometer !== null && $item->odometer_start > $prevOdometer) {
                        $totalEffDistance += ($item->odometer_start - $prevOdometer);
                    }
                    $prevOdometer = $item->odometer_start;
                    $totalEffLiters += $item->fuel_liters;
                }
            }
            $avgEfficiency = $totalEffDistance > 0 ? round(($totalEffLiters / $totalEffDistance) * 100, 2) : null;
        }

        return [
            'total_fuel_cost'        => $totalFuel,
            'total_service_cost'     => $totalService,
            'total_repair_cost'      => $totalRepair,
            'total_operational_cost' => $totalFuel + $totalService + $totalRepair,
            'total_distance'         => $totalDistance,
            'pending_verification'   => $pendingLogs,
            'total_trips'            => $totalTrips,
            'avg_efficiency'         => $avgEfficiency,
        ];
    }

    private function getTransportDistribution($buId, $dateFrom, $dateTo, $vehicleId = null, $driverId = null)
    {
        $query = DriverRequest::whereIn('status', ['approved_admin', 'completed'])
            ->whereBetween('usage_date', [$dateFrom, $dateTo]);
        if ($buId) $this->applyBusinessUnitFilter($query, $buId);
        if ($vehicleId) $query->where('vehicle_id', $vehicleId);
        if ($driverId) $query->where('driver_id', $driverId);
        return $query->select('transport_type', DB::raw('count(*) as total'))
            ->groupBy('transport_type')
            ->get();
    }

    private function getEfficiencyData($buId, $vehicleId = null, $driverId = null, $fuelGroup = null)
    {
        $fuelLogs = FuelLog::with('vehicle')
            ->where('is_verified', 1)
            ->when($buId, fn ($q) => $q->whereHas('vehicle', fn ($sq) => $sq->where('business_unit_id', $buId)))
            ->when($vehicleId, fn ($q) => $q->where('vehicle_id', $vehicleId))
            ->when($driverId, fn ($q) => $q->where('driver_id', $driverId))
            ->orderBy('vehicle_id')->orderBy('filling_date');
        $fuelLogs = $this->applyFuelGroupFilter($fuelLogs, $fuelGroup, false)->get();

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
                    'vehicle'        => $vehicle->plate_number,
                    'type'           => $vehicle->type,
                    // fuel_type: Bensin/Solar/Listrik/Hybrid/Lainnya (sesuai master kendaraan).
                    // unit: satuan konsumsi — "kWh" untuk kendaraan listrik, "Liter" untuk lainnya,
                    // konsisten dengan cara FuelLogController/analytics bedain BBM vs listrik.
                    'fuel_type'      => $vehicle->fuel_type,
                    'unit'           => $vehicle->fuel_type === 'Listrik' ? 'kWh' : 'Liter',
                    'avg_efficiency' => $avgConsumption,
                    'total_trips'    => $items->count(),
                ];
            }
        }
        return collect($result)->sortBy('avg_efficiency')->take(10)->values();
    }

    private function getVehicleStatsForPeriod($buId, $dateFrom, $dateTo, $vehicleId = null, $driverId = null, $fuelGroup = null)
    {
        $vehicles = Vehicle::when($buId, fn ($q) => $q->where('business_unit_id', $buId))
            ->when($vehicleId, fn ($q) => $q->where('id', $vehicleId));
        $vehicles = $this->applyFuelGroupFilter($vehicles, $fuelGroup, true)->get();

        $stats = [];
        foreach ($vehicles as $vehicle) {
            $fuelQuery = FuelLog::where('vehicle_id', $vehicle->id)
                ->where('is_verified', 1)
                ->whereBetween('filling_date', [$dateFrom, $dateTo]);
            if ($driverId) $fuelQuery->where('driver_id', $driverId);
            $fuelCost = $fuelQuery->sum(DB::raw('fuel_liters * fuel_price_per_liter'));

            $serviceCost = ServiceSchedule::where('vehicle_id', $vehicle->id)
                ->whereBetween('service_date', [$dateFrom, $dateTo])
                ->sum('cost');

            $repairCost = Repair::where('vehicle_id', $vehicle->id)
                ->whereBetween('report_date', [$dateFrom, $dateTo])
                ->sum('total_cost');

            $fuelLogs = FuelLog::where('vehicle_id', $vehicle->id)
                ->where('is_verified', 1)
                ->whereBetween('filling_date', [$dateFrom, $dateTo]);
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
                ->whereBetween('filling_date', [$dateFrom, $dateTo]);
            if ($driverId) $fuelLiters->where('driver_id', $driverId);
            $fuelLiters = $fuelLiters->sum('fuel_liters');

            if ($fuelCost > 0 || $serviceCost > 0 || $repairCost > 0 || $totalDistance > 0) {
                $stats[] = [
                    'plate_number' => $vehicle->plate_number,
                    'fuel_type'    => $vehicle->fuel_type,
                    'unit'         => $vehicle->fuel_type === 'Listrik' ? 'kWh' : 'Liter',
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