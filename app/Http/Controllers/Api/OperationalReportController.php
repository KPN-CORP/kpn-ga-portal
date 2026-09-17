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
 *
 * v3.1 — TAMBAHAN atas permintaan AMS (15 Sep 2026):
 * - Param company_code (cocok ke db_asset_vehicles.asset_owner_code, mis.
 *   "CD") untuk menyaring hasil ke 1 perusahaan pemilik aset AMS.
 * - Param company (cocok SEBAGIAN ke owner_company_name, mis. "cisadane")
 *   — AMS lebih sering punya nama perusahaan daripada kode, jadi ini yang
 *   dipakai duluan.
 *   Dua-duanya BELUM MENYARING seluruh bagian "summary" (total_fuel_cost
 *   dkk) dan "transport_distribution" — dua bagian itu dihitung oleh trait
 *   CalculatesOperationalStats yang cuma nerima 1 vehicle_id, bukan
 *   daftar. Yang SUDAH disaring: vehicle_breakdown, efficiency_top10,
 *   dan 4 field jumlah-aset (total_fuel_asset_bbm dkk).
 *   Lihat catatan TODO di resolveCompanyVehicleIds().
 *
 * v3.3 — TAMBAHAN atas permintaan AMS (15 Sep 2026):
 * - Param plate_number (cocok SEBAGIAN ke drms_vehicles.plate_number,
 *   dinormalisasi: hilangkan spasi, tidak case-sensitive, mis. "1929"
 *   cocok dengan "B 1929 SDW") — AMS bisa cari ringkasan 1 kendaraan
 *   tanpa perlu tahu vehicle_id DRMS-nya, cukup pakai plat nomor, sama
 *   seperti param plate_number yang sudah dipakai di endpoint 1-7.
 *   Boleh dipakai BARENGAN company_code/company (semuanya harus cocok —
 *   AND), lewat resolvePlateVehicleIds() + combineVehicleIdFilters().
 *   Cakupan penyaringannya SAMA seperti company_code/company: menyaring
 *   vehicle_breakdown, efficiency_top10, dan 4 field jumlah-aset — BELUM
 *   menyaring "summary" & "transport_distribution" (lihat TODO yang
 *   sama di resolveCompanyVehicleIds()).
 */
class OperationalReportController extends Controller
{
    use CalculatesOperationalStats;

    /**
     * BARU (v3.1/v3.2) — cari daftar vehicle_id DRMS milik 1 perusahaan AMS,
     * lewat pencocokan plat nomor (dinormalisasi: hilangkan spasi, uppercase),
     * sama seperti pencocokan owner yang sudah dipakai OwnerLookupCache.
     *
     * $companyCode  → cocok persis ke db_asset_vehicles.asset_owner_code
     *                 (mis. "CD").
     * $companyName  → cocok SEBAGIAN (LIKE, tidak case-sensitive) ke
     *                 db_asset_vehicles.owner_company_name (mis. "cisadane"
     *                 cocok dengan "PT.CISADANE RAYA CHEMICAL"). Ini yang
     *                 dipakai param "company" atas permintaan AMS (15 Sep
     *                 2026) — mereka isi nama perusahaan, bukan kode.
     * Kalau dua-duanya dikirim, keduanya WAJIB cocok (AND).
     *
     * db_asset_vehicles ada di DB DRMS sendiri (hasil sinkron dari AMS),
     * jadi query langsung ke situ, bukan ke sistem AMS.
     *
     * TODO: kalau CalculatesOperationalStats sudah bisa nerima daftar
     * vehicle_id (bukan cuma 1), sambungkan juga ke getOperationalStats()
     * & getTransportDistribution() supaya param ini menyaring seluruh
     * response, bukan cuma vehicle_breakdown/efficiency_top10/asset-count.
     */
    private function resolveCompanyVehicleIds(?string $companyCode, ?string $companyName): array
    {
        $query = DB::table('db_asset_vehicles')->whereNotNull('registration_plates');

        if ($companyCode) {
            $query->where('asset_owner_code', $companyCode);
        }
        if ($companyName) {
            $query->where('owner_company_name', 'like', '%' . $companyName . '%');
        }

        $plates = $query->pluck('registration_plates')
            ->map(fn ($p) => strtoupper(str_replace(' ', '', $p)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($plates)) {
            return [];
        }

        return DB::table('drms_vehicles')
            ->whereRaw(
                "UPPER(REPLACE(plate_number, ' ', '')) IN (" . implode(',', array_fill(0, count($plates), '?')) . ')',
                $plates
            )
            ->pluck('id')
            ->all();
    }

    /**
     * BARU (v3.3) — cari daftar vehicle_id DRMS lewat pencocokan SEBAGIAN
     * plat nomor (dinormalisasi: hilangkan spasi, uppercase), persis pola
     * yang sudah dipakai plate_number di ServiceScheduleController /
     * RepairController / FuelLogController — bedanya di sini hasilnya
     * daftar vehicle_id (dipakai buat filter internal), bukan query
     * langsung ke tabel service/repair/fuel_log.
     *
     * $plate boleh sebagian, mis. "1929" cocok dengan plat manapun yang
     * mengandung "1929" (termasuk "B 1929 SDW").
     */
    private function resolvePlateVehicleIds(string $plate): array
    {
        $normalized = strtoupper(str_replace(' ', '', $plate));

        return DB::table('drms_vehicles')
            ->whereRaw("UPPER(REPLACE(plate_number, ' ', '')) LIKE ?", ["%{$normalized}%"])
            ->pluck('id')
            ->all();
    }

    /**
     * BARU (v3.3) — gabungkan 2 hasil filter vehicle_id (company & plate)
     * jadi 1 daftar akhir, dengan aturan:
     * - null + null   -> null (tidak ada filter sama sekali)
     * - null + array  -> array itu (cuma 1 filter yang dikirim)
     * - array + array -> irisan (AND) — vehicle_id harus cocok KEDUANYA
     *
     * Array kosong itu valid & beda dari null: artinya filter dikirim
     * tapi tidak ada yang cocok, jadi hasil akhir memang harus kosong
     * (0 kendaraan), bukan diabaikan.
     */
    private function combineVehicleIdFilters(?array $a, ?array $b): ?array
    {
        if ($a === null) {
            return $b;
        }
        if ($b === null) {
            return $a;
        }

        return array_values(array_intersect($a, $b));
    }

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
     * - company_code          : BARU (v3.1) — kode pemilik aset AMS persis
     *                           (mis. "CD"), cocok field owner.code.
     * - company                : BARU (v3.2, atas permintaan AMS 15 Sep 2026)
     *                           — NAMA perusahaan pemilik aset, boleh sebagian
     *                           (mis. "cisadane" cocok "PT.CISADANE RAYA
     *                           CHEMICAL"), cocok field owner.company_name.
     *                           Beda dari company_code — ini teks bebas, bukan
     *                           kode. Boleh dipakai bareng company_code
     *                           (keduanya harus cocok kalau dua-duanya dikirim).
     *                           Sama seperti company_code: menyaring
     *                           vehicle_breakdown, efficiency_top10, dan 4
     *                           field jumlah-aset. Tidak ada yang cocok ->
     *                           bagian itu kosong/0, bukan error.
     * - plate_number          : BARU (v3.3, atas permintaan AMS 15 Sep 2026)
     *                           — cari ringkasan lewat plat nomor, boleh
     *                           sebagian (mis. "1929" cocok "B 1929 SDW"),
     *                           sama seperti plate_number di endpoint 1-7.
     *                           Boleh dipakai bareng company_code/company
     *                           (semuanya harus cocok — AND). Cakupan
     *                           penyaringan sama seperti company_code/
     *                           company: vehicle_breakdown, efficiency_top10,
     *                           dan 4 field jumlah-aset. Tidak ada yang
     *                           cocok -> bagian itu kosong/0, bukan error.
     */
    public function summary(Request $request)
    {
        $buId = $request->get('business_unit_id');
        $vehicleId = $request->get('vehicle_id');
        $driverId = $request->get('driver_id');
        $fuelGroup = $request->get('fuel_type');
        $companyCode = $request->get('company_code');
        $companyName = $request->get('company');
        $plateNumber = $request->get('plate_number');

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

        // BARU — daftar vehicle_id DRMS milik company_code/company yang diminta.
        // null = tidak dikirim sama sekali (tidak difilter). [] = dikirim tapi
        // tidak ada plat yang cocok di DRMS (hasil kosong).
        $companyVehicleIds = ($companyCode || $companyName)
            ? $this->resolveCompanyVehicleIds($companyCode, $companyName)
            : null;

        // BARU (v3.3) — daftar vehicle_id DRMS yang platnya cocok (sebagian)
        // dengan param plate_number. null = tidak dikirim, [] = dikirim tapi
        // tidak ada plat yang cocok.
        $plateVehicleIds = $plateNumber
            ? $this->resolvePlateVehicleIds($plateNumber)
            : null;

        // BARU (v3.3) — gabungkan filter company + plate jadi 1 daftar
        // vehicle_id akhir (AND kalau dua-duanya dikirim).
        $filterVehicleIds = $this->combineVehicleIdFilters($companyVehicleIds, $plateVehicleIds);

        // "v3" + company + plate ditambahkan di cache key supaya tiap kombinasi
        // filter punya entry cache sendiri, tidak nabrak cache lain.
        $cacheKey = "api.operational-summary.v3.{$buId}.{$dateFrom}.{$dateTo}.{$vehicleId}.{$driverId}.{$fuelGroup}."
            . ($companyCode ?: '-') . '.' . ($companyName ?: '-') . '.' . ($plateNumber ?: '-');

        $payload = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($buId, $dateFrom, $dateTo, $vehicleId, $driverId, $fuelGroup, $filterVehicleIds, $companyCode, $companyName, $plateNumber) {
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
                }),
                $filterVehicleIds
            );
            $summary['total_fuel_asset_listrik'] = $this->countDistinctVehicles(
                FuelLog::query(), 'filling_date', $buId, $vehicleId, $driverId, $dateFrom, $dateTo,
                fn ($q) => $q->whereHas('vehicle', fn ($vq) => $vq->where('fuel_type', 'Listrik')),
                $filterVehicleIds
            );
            $summary['total_maintenance_asset_service_schedules'] = $this->countDistinctVehicles(
                ServiceSchedule::query(), 'service_date', $buId, $vehicleId, null, $dateFrom, $dateTo,
                null, $filterVehicleIds
            );
            $summary['total_maintenance_asset_repairment'] = $this->countDistinctVehicles(
                Repair::query(), 'report_date', $buId, $vehicleId, null, $dateFrom, $dateTo,
                null, $filterVehicleIds
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

                // BARU — total_cost per kendaraan, pola sama seperti yang sudah
                // dipakai AdminOperationalController (dashboard & Export CSV),
                // supaya AMS tidak perlu jumlahkan sendiri fuel/service/repair.
                $row['total_cost'] = (float) ($row['fuel_cost'] ?? 0)
                    + (float) ($row['service_cost'] ?? 0)
                    + (float) ($row['repair_cost'] ?? 0);

                return $row;
            })->values()->all();

            $efficiencyTop10 = collect($efficiencyTop10)->map(function ($row) {
                $row = (array) $row;
                $row['owner'] = OwnerLookupCache::get($row['vehicle'] ?? null);
                return $row;
            })->values()->all();

            // BARU — company_code/company/plate_number menyaring vehicle_breakdown
            // & efficiency_top10. company_code cocok persis owner.code, company
            // cocok SEBAGIAN (tidak case-sensitive) ke owner.company_name,
            // plate_number (BARU v3.3) cocok SEBAGIAN (dinormalisasi: hilangkan
            // spasi, tidak case-sensitive) ke plate_number/vehicle baris ybs.
            // Semua kondisi yang dikirim harus cocok (AND). efficiency_top10
            // masih dihitung dari SEMUA kendaraan dulu baru disaring di sini,
            // jadi bisa < 10 baris kalau hasil filternya kecil (belum bisa
            // disaring dari sumbernya — lihat TODO di resolveCompanyVehicleIds()).
            if ($companyCode || $companyName || $plateNumber) {
                $normalizedPlateQuery = $plateNumber ? strtoupper(str_replace(' ', '', $plateNumber)) : null;

                $matchesFilter = function ($row) use ($companyCode, $companyName, $normalizedPlateQuery) {
                    if ($companyCode || $companyName) {
                        $owner = $row['owner'] ?? null;
                        if (! $owner) {
                            return false;
                        }
                        if ($companyCode && ($owner['code'] ?? null) !== $companyCode) {
                            return false;
                        }
                        if ($companyName && stripos($owner['company_name'] ?? '', $companyName) === false) {
                            return false;
                        }
                    }

                    if ($normalizedPlateQuery) {
                        $rowPlate = strtoupper(str_replace(' ', '', $row['plate_number'] ?? $row['vehicle'] ?? ''));
                        if (strpos($rowPlate, $normalizedPlateQuery) === false) {
                            return false;
                        }
                    }

                    return true;
                };

                $vehicleBreakdown = collect($vehicleBreakdown)->filter($matchesFilter)->values()->all();
                $efficiencyTop10 = collect($efficiencyTop10)->filter($matchesFilter)->values()->all();

                // BARU — sebelumnya "summary" (total_fuel_cost dkk) TIDAK ikut
                // tersaring company_code/company/plate_number (lihat TODO lama
                // di resolveCompanyVehicleIds()), jadi tetap angka SELURUH BU
                // meski request-nya minta 1 kendaraan/perusahaan. Sekarang
                // dihitung ulang dari vehicle_breakdown yang SUDAH difilter,
                // supaya summary konsisten dengan vehicle_breakdown yang
                // dikembalikan di response yang sama.
                //
                // Catatan: pending_verification, total_trips, dan avg_efficiency
                // BELUM ikut disaring di sini — field itu butuh query terpisah
                // per-kendaraan (bukan hasil agregat vehicle_breakdown), jadi
                // masih ambil angka seluruh BU untuk sementara.
                $summary['total_fuel_cost'] = collect($vehicleBreakdown)->sum(fn ($r) => (float) $r['fuel_cost']);
                $summary['total_service_cost'] = collect($vehicleBreakdown)->sum(fn ($r) => (float) $r['service_cost']);
                $summary['total_repair_cost'] = collect($vehicleBreakdown)->sum(fn ($r) => (float) $r['repair_cost']);
                $summary['total_operational_cost'] = $summary['total_fuel_cost']
                    + $summary['total_service_cost']
                    + $summary['total_repair_cost'];
                $summary['total_distance'] = collect($vehicleBreakdown)->sum(fn ($r) => (float) ($r['distance'] ?? 0));
            }

            return [
                'period'                 => ['date_from' => $dateFrom, 'date_to' => $dateTo],
                'business_unit_id'       => $buId ? (int) $buId : null,
                'company_code'           => $companyCode ?: null,
                'company'                => $companyName ?: null,
                'plate_number'           => $plateNumber ?: null,
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
        ?callable $extra = null,
        ?array $companyVehicleIds = null
    ): int {
        $query->whereHas('vehicle', function ($q) use ($buId, $vehicleId) {
            if ($buId) {
                $q->where('business_unit_id', $buId);
            }
            if ($vehicleId) {
                $q->where('id', $vehicleId);
            }
        });

        // BARU — filter company_code/company: null = tidak difilter, array
        // (termasuk kosong) = harus masuk daftar vehicle_id company tsb.
        // Array kosong sengaja bikin hasil 0, bukan diabaikan.
        if ($companyVehicleIds !== null) {
            $query->whereIn('vehicle_id', $companyVehicleIds);
        }

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