<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\CalculatesOperationalStats;
use App\Models\Drms\TripLog;
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

        $cacheKey = "api.operational-summary.{$buId}.{$dateFrom}.{$dateTo}.{$vehicleId}.{$driverId}.{$fuelGroup}";

        $payload = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($buId, $dateFrom, $dateTo, $vehicleId, $driverId, $fuelGroup) {
            // Batasi waktu eksekusi query di sesi ini — kalau ada yang nyangkut,
            // MySQL yang hentikan sendiri, gak jalan tanpa batas dan gak ganggu
            // resource DB untuk user GA Portal lain yang lagi pakai bersamaan.
            DB::statement('SET SESSION MAX_EXECUTION_TIME=15000');

            return [
                'period'                 => ['date_from' => $dateFrom, 'date_to' => $dateTo],
                'business_unit_id'       => $buId ? (int) $buId : null,
                'summary'                => $this->getOperationalStats($buId, $dateFrom, $dateTo, $vehicleId, $driverId),
                'transport_distribution' => $this->getTransportDistribution($buId, $dateFrom, $dateTo, $vehicleId, $driverId),
                'efficiency_top10'       => $this->getEfficiencyData($buId, $vehicleId, $driverId, $fuelGroup),
                'vehicle_breakdown'      => $this->getVehicleStatsForPeriod($buId, $dateFrom, $dateTo, $vehicleId, $driverId, $fuelGroup),
                'generated_at'           => now()->toIso8601String(),
            ];
        });

        return response()->json($payload);
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

        $tripLogs->getCollection()->transform(function ($log) {
            return [
                'id'           => $log->id,
                'request_no'   => $log->request->request_no ?? null,
                'driver'       => $log->request->driver->name ?? null,
                'plate_number' => $log->request->vehicle->plate_number ?? null,
                'usage_date'   => $log->request->usage_date,
                'distance_km'  => max(0, ($log->odometer_finish ?? 0) - ($log->odometer_start ?? 0)),
                'fuel_volume'  => $log->fuel_volume,
                'fuel_cost'    => $log->fuel_cost,
                'updated_at'   => $log->updated_at,
            ];
        });

        return response()->json($tripLogs);
    }
}