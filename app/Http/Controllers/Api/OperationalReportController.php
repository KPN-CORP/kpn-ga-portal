<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\CalculatesOperationalStats;
use App\Models\Drms\FuelLog;
use App\Models\Drms\Repair;
use App\Models\Drms\ServiceSchedule;
use App\Models\Drms\TripLog;
use App\Support\OwnerLookupCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * API Ringkasan Operasional — VIEW ONLY, dipakai AMS untuk membangun report
 * yang setara dengan menu Export CSV di GA Portal (Operational Dashboard).
 *
 * SENGAJA dipecah jadi 2 endpoint, bukan 1 endpoint besar niru CSV mentah:
 * - summary()  → bagian yang ukurannya selalu kecil & tetap berapa pun
 *                rentang tanggalnya (Ringkasan, Distribusi Transportasi,
 *                Efisiensi Top 10, Rincian per Kendaraan) → aman di-cache.
 * - tripLogs() → raw listing (bisa besar kalau ditarik bertahun-tahun),
 *                jadi WAJIB dipaginate, sama seperti fuel-logs/
 *                service-schedules/repairs yang sudah ada.
 *
 * Logic perhitungannya dari trait CalculatesOperationalStats — sama persis
 * dengan yang dipakai Export CSV di AdminOperationalController, supaya
 * angkanya konsisten dan gak perlu maintain 2 versi logic yang berbeda.
 *
 * v3.0 — TAMBAHAN (tidak ada yang dihapus/diubah dari v2.0):
 * - 4 field baru di "summary": total_fuel_asset_bbm, total_fuel_asset_listrik,
 *   total_maintenance_asset_service_schedules, total_maintenance_asset_repairment
 *   — dihitung sebagai jumlah KENDARAAN UNIK (distinct vehicle_id), bukan
 *   jumlah baris/record, dengan filter BU/vehicle/driver/tanggal yang sama.
 * - Blok "owner" (data pemilik aset dari AMS, tabel db_asset_vehicles) di
 *   setiap item vehicle_breakdown, efficiency_top10, dan trip-logs,
 *   disinkronkan berdasarkan plat nomor.
 */
class OperationalReportController extends Controller
{
    use CalculatesOperationalStats;

    /**
     * GET /api/v1/operational-summary
     *
     * Query params:
     * - business_unit_id     : WAJIB kalau mau data 1 BU tertentu. Kalau
     *                           dikosongkan, dihitung untuk SEMUA BU.
     * - date_from / date_to  : rentang tanggal bebas (mis. 1 Jan - 31 Des),
     *                           berapa pun panjangnya — hasilnya tetap kecil
     *                           (top 10 / per kendaraan), jadi aman.
     * - month / year          : alternatif kalau cuma mau 1 bulan (dipakai
     *                           kalau date_from/date_to tidak dikirim).
     * - vehicle_id/driver_id  : opsional
     * - fuel_type             : opsional — "listrik" (kendaraan EV/Charger) atau
     *                           "bbm" (semua selain listrik: Bensin/Solar/Hybrid/
     *                           Lainnya/kosong, digabung — sama seperti pengelompokan
     *                           yang sudah dipakai di halaman analytics BBM).
     */
    public function summary(Request $request)
    {
        $buId = $request->get('business_unit_id');
        $vehicleId = $request->get('vehicle_id');
        $driverId = $request->get('driver_id');
        $fuelGroup = $request->get('fuel_type');

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $dateFrom = \Carbon\Carbon::parse($request->date_from)->toDateString();
            $dateTo = \Carbon\Carbon::parse($request->date_to)->format('Y-m-d 23:59:59');
        } else {
            $month = (int) $request->get('month', now()->month);
            $year = (int) $request->get('year', now()->year);
            abort_if($month < 1 || $month > 12, 422, 'Parameter month harus antara 1-12.');
            $dateFrom = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
            $dateTo = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d 23:59:59');
        }

        abort_if(\Carbon\Carbon::parse($dateFrom)->gt(\Carbon\Carbon::parse($dateTo)), 422, 'date_from harus sebelum date_to.');

        // "v3" ditambahkan di cache key supaya request pertama setelah deploy
        // TIDAK kebaca dari cache lama (v2.0) yang belum punya field/owner baru.
        $cacheKey = "api.operational-summary.v3.{$buId}.{$dateFrom}.{$dateTo}.{$vehicleId}.{$driverId}.{$fuelGroup}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($buId, $dateFrom, $dateTo, $vehicleId, $driverId, $fuelGroup) {
            // Batasi waktu eksekusi query di sesi ini — kalau ada yang nyangkut,
            // MySQL yang hentikan sendiri, gak jalan tanpa batas dan gak ganggu
            // resource DB untuk user GA Portal lain yang lagi pakai bersamaan.
            DB::statement('SET SESSION MAX_EXECUTION_TIME=15000');

            $summary = $this->getOperationalStats($buId, $dateFrom, $dateTo, $vehicleId, $driverId);

            // Ambil breakdown TANPA filter fuel_type dulu (semua kendaraan),
            // supaya split bbm/listrik selalu lengkap walau request-nya
            // pakai fuel_type=bbm atau fuel_type=listrik. Kalau $fuelGroup
            // kosong, breakdown ini juga langsung dipakai buat response
            // (gak perlu query 2x).
            $fullBreakdown = $this->getVehicleStatsForPeriod($buId, $dateFrom, $dateTo, $vehicleId, $driverId, null);

            $summary['total_fuel_cost_listrik'] = collect($fullBreakdown)
                ->where('fuel_type', 'Listrik')
                ->sum('fuel_cost');
            $summary['total_fuel_cost_bbm'] = collect($fullBreakdown)
                ->where('fuel_type', '!=', 'Listrik')
                ->sum('fuel_cost');

            // BARU (v3.0) — jumlah KENDARAAN UNIK (distinct), bukan jumlah baris/record
            //
            // FIX: fuel_type adalah kolom di tabel vehicle (drms_vehicles), BUKAN
            // di drms_fuel_logs — makanya harus difilter lewat whereHas('vehicle', ...),
            // bukan langsung di $q (query FuelLog). Sebelumnya ini menyebabkan
            // SQLSTATE[42S22]: Unknown column 'fuel_type' in 'where clause'.
            //
            // fuel_type di kendaraan bisa NULL (belum diisi) — "fuel_type != 'Listrik'"
            // akan SKIP baris NULL (NULL != 'Listrik' = NULL, bukan true), jadi sisi BBM
            // harus eksplisit ikutkan orWhereNull juga, sama seperti pola yang sudah
            // dipakai di Drms c/FuelLogController.php.
            $summary['total_fuel_asset_bbm'] = $this->countDistinctVehicles(
                FuelLog::query(), 'filling_date', $buId, $vehicleId, $driverId, $dateFrom, $dateTo,
                fn ($q) => $q->whereHas('vehicle', function ($vq) {
                    $vq->where('fuel_type', '!=', 'Listrik')->orWhereNull('fuel_type');
                })
            );
            $summary['total_fuel_asset_listrik'] = $this->countDistinctVehicles(
                FuelLog::query(), 'filling_date', $buId, $vehicleId, $driverId, $dateFrom, $dateTo,
                fn ($q) => $q->whereHas('vehicle', fn ($vq) => $vq->where('fuel_type', 'Listrik'))
            );
            $summary['total_maintenance_asset_service_schedules'] = $this->countDistinctVehicles(
                ServiceSchedule::query(), 'service_date', $buId, $vehicleId, null, $dateFrom, $dateTo
            );
            $summary['total_maintenance_asset_repairment'] = $this->countDistinctVehicles(
                Repair::query(), 'report_date', $buId, $vehicleId, null, $dateFrom, $dateTo
            );

            $vehicleBreakdown = $fuelGroup
                ? $this->getVehicleStatsForPeriod($buId, $dateFrom, $dateTo, $vehicleId, $driverId, $fuelGroup)
                : $fullBreakdown;

            $efficiencyTop10 = $this->getEfficiencyData($buId, $vehicleId, $driverId, $fuelGroup);
            $transportDistribution = $this->getTransportDistribution($buId, $dateFrom, $dateTo, $vehicleId, $driverId);

            // BARU (v3.0) — preload owner untuk semua plat yang tampil di
            // vehicle_breakdown & efficiency_top10 (1 query batch, bukan N+1)
            OwnerLookupCache::preload(
                collect($vehicleBreakdown)->pluck('plate_number')
                    ->merge(collect($efficiencyTop10)->pluck('vehicle'))
                    ->filter()->unique()->values()->all()
            );

            $vehicleBreakdown = collect($vehicleBreakdown)->map(function ($row) {
                $row = (array) $row;
                $row['owner'] = OwnerLookupCache::get($row['plate_number'] ?? null);
                return $row;
            })->values()->all();

            $efficiencyTop10 = collect($efficiencyTop10)->map(function ($row) {
                $row = (array) $row;
                $row['owner'] = OwnerLookupCache::get($row['vehicle'] ?? null);
                return $row;
            })->values()->all();

            return [
                'period'                 => ['date_from' => $dateFrom, 'date_to' => $dateTo],
                'business_unit_id'       => $buId ? (int) $buId : null,
                'summary'                => $summary,
                'transport_distribution' => $transportDistribution,
                'efficiency_top10'       => $efficiencyTop10,
                'vehicle_breakdown'      => $vehicleBreakdown,
                'generated_at'           => now()->toIso8601String(),
            ];
        });

        return response()->json($payload);
    }

    /**
     * BARU (v3.0) — helper generik: hitung jumlah KENDARAAN UNIK
     * (distinct vehicle_id) yang punya record di $query, dengan filter
     * BU/vehicle/driver/tanggal yang sama dipakai endpoint lain.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query   query dasar model (FuelLog/ServiceSchedule/Repair)
     * @param string $dateColumn   kolom tanggal yang difilter (filling_date/service_date/report_date)
     * @param callable|null $extra callback tambahan (mis. filter fuel_type)
     */
    private function countDistinctVehicles(
        $query,
        string $dateColumn,
        $buId,
        $vehicleId,
        $driverId,
        string $dateFrom,
        string $dateTo,
        ?callable $extra = null
    ): int {
        $query->whereHas('vehicle', function ($q) use ($buId, $vehicleId) {
            if ($buId) {
                $q->where('business_unit_id', $buId);
            }
            if ($vehicleId) {
                $q->where('id', $vehicleId);
            }
        });

        // ServiceSchedule & Repair tidak punya kolom driver_id — guard ini
        // supaya tidak error "Unknown column" kalau $driverId dikirim.
        if ($driverId && $query->getModel()->isFillable('driver_id')) {
            $query->where('driver_id', $driverId);
        }

        if ($extra) {
            $extra($query);
        }

        $query->whereDate($dateColumn, '>=', $dateFrom)
              ->whereDate($dateColumn, '<=', $dateTo);

        return (int) $query->distinct('vehicle_id')->count('vehicle_id');
    }

    /**
     * GET /api/v1/trip-logs
     * Raw listing perjalanan (TripLog) yang sudah TERVERIFIKASI — setara
     * bagian "TRIP LOGS (Terverifikasi)" di Export CSV, tapi dipaginate.
     *
     * Query params:
     * - business_unit_id, vehicle_id, driver_id : opsional, filter
     * - date_from / date_to : filter usage_date. Maksimal rentang 366 hari
     *                          (1 tahun) KALAU tidak disertai updated_since.
     * - updated_since        : ISO datetime, buat sinkronisasi berkala
     *                          (rekomendasi utama untuk tarik data > 1 tahun).
     * - per_page             : default 20, maksimal 100
     */
    public function tripLogs(Request $request)
    {
        if ($request->filled('date_from') && $request->filled('date_to') && !$request->filled('updated_since')) {
            $days = \Carbon\Carbon::parse($request->date_from)->diffInDays($request->date_to);
            abort_if($days > 366, 422, 'Rentang date_from-date_to maksimal 366 hari kalau tanpa updated_since. Gunakan updated_since untuk menarik data lebih lama.');
        }

        $buId = $request->get('business_unit_id');

        $query = TripLog::with(['request.vehicle', 'request.driver'])
            ->where('is_verified', 1)
            ->whereHas('request', function ($q) use ($buId, $request) {
                if ($buId) {
                    $this->applyBusinessUnitFilter($q, $buId);
                }
                if ($request->filled('vehicle_id')) $q->where('vehicle_id', $request->vehicle_id);
                if ($request->filled('driver_id')) $q->where('driver_id', $request->driver_id);
                if ($request->filled('date_from')) $q->whereDate('usage_date', '>=', $request->date_from);
                if ($request->filled('date_to')) $q->whereDate('usage_date', '<=', $request->date_to);
            });

        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->updated_since);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);

        // cursorPaginate, BUKAN paginate — supaya konsisten cepat walau ditarik
        // sampai halaman ke-sekian, gak makin lambat kayak OFFSET biasa.
        $tripLogs = $query->orderBy('updated_at')->cursorPaginate($perPage)->withQueryString();

        // BARU (v3.0): preload owner untuk semua plat di halaman ini (1 query batch)
        OwnerLookupCache::preload(
            $tripLogs->getCollection()
                ->map(fn ($log) => $log->request->vehicle->plate_number ?? null)
                ->all()
        );

        $tripLogs->getCollection()->transform(function ($log) {
            $plateNumber = $log->request->vehicle->plate_number ?? null;

            return [
                'id'           => $log->id,
                'request_no'   => $log->request->request_no ?? null,
                'driver'       => $log->request->driver->name ?? null,
                'plate_number' => $plateNumber,
                'usage_date'   => $log->request->usage_date,
                'distance_km'  => max(0, ($log->odometer_finish ?? 0) - ($log->odometer_start ?? 0)),
                'fuel_volume'  => $log->fuel_volume,
                'fuel_cost'    => $log->fuel_cost,
                'updated_at'   => $log->updated_at,

                // BARU (v3.0)
                'owner'        => OwnerLookupCache::get($plateNumber),
            ];
        });

        return response()->json($tripLogs);
    }
}