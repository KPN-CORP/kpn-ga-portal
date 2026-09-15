<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\FuelLog;
use App\Models\Drms\Vehicle;
use App\Models\Drms\Driver;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FuelLogController extends Controller
{
    private function getBusinessUnitId()
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin()) return null;
        return $user->drmsProfile->business_unit_id ?? abort(403);
    }

    public function index(Request $request)
    {
        $buId = $this->getBusinessUnitId();
        $query = FuelLog::with('vehicle', 'driver', 'user', 'verifier');
        if ($buId) {
            $query->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('filling_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('filling_date', '<=', $request->date_to);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->whereHas('vehicle', fn($sq) => $sq->where('plate_number', 'LIKE', $search))
                  ->orWhereHas('driver', fn($sq) => $sq->where('name', 'LIKE', $search));
            });
        }
        if ($request->filled('status')) {
            $query->where('is_verified', $request->status == 'verified' ? 1 : 0);
        }

        // Filter Bulan (default: bulan sekarang), kecuali sudah pakai filter tanggal manual.
        // Pilih "Semua Bulan" (month=all) untuk menonaktifkan filter ini.
        $month = $request->get('month', now()->format('Y-m'));
        if (!$request->filled('date_from') && !$request->filled('date_to') && $month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [$year, $monthNum] = explode('-', $month);
            $query->whereYear('filling_date', $year)->whereMonth('filling_date', $monthNum);
        }

        // PENTING: hitung statistik dari QUERY PENUH (semua filter di atas sudah masuk,
        // sebelum di-paginate) — supaya angkanya total keseluruhan, bukan cuma 20 data
        // di halaman yang sedang tampil.
        $totalLogs     = (clone $query)->count();
        $verifiedCount = (clone $query)->where('is_verified', 1)->count();
        $pendingCount  = (clone $query)->where('is_verified', 0)->count();

        // Liter (BBM) dan kWh (Listrik) dipisah, bukan digabung jadi satu angka —
        // karena satuannya beda, jumlahin keduanya jadi satu tidak ada artinya.
        // Biayanya (Rp) juga dipisah per kategori, biar tiap kartu nampilin nominal
        // yang benar-benar sesuai isinya (bukan total gabungan semua log).
        // PENTING: fuel_type di kendaraan bisa NULL (belum diisi) — di SQL,
        // "fuel_type != 'Listrik'" akan SKIP baris yang NULL (NULL != 'Listrik' = NULL,
        // bukan true), jadi harus eksplisit ikutkan whereNull juga supaya kendaraan
        // yang fuel_type-nya belum diisi tetap terhitung sebagai BBM (default), bukan
        // hilang dari kedua total.
        $bbmQuery = (clone $query)->whereHas('vehicle', function ($q) {
            $q->where('fuel_type', '!=', 'Listrik')->orWhereNull('fuel_type');
        });
        $totalLiters = (clone $bbmQuery)->sum('fuel_liters');
        $totalCostBbm = (clone $bbmQuery)->sum('total_cost');

        $listrikQuery = (clone $query)->whereHas('vehicle', fn($q) => $q->where('fuel_type', 'Listrik'));
        $totalKwh = (clone $listrikQuery)->sum('fuel_liters');
        $totalCostListrik = (clone $listrikQuery)->sum('total_cost');

        $logs = $query->latest()->paginate(20)->appends($request->query());
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))->get();
        return view('drms.fuel_logs.index', compact(
            'logs', 'vehicles', 'month', 'totalLogs', 'verifiedCount', 'pendingCount',
            'totalLiters', 'totalCostBbm', 'totalKwh', 'totalCostListrik'
        ));
    }

    public function create()
    {
        // PERBAIKAN: driver sebelumnya hanya bisa isi BBM untuk kendaraan di
        // Business Unit-nya sendiri (dibatasi $buId). Sekarang driver boleh
        // isi BBM untuk kendaraan MANAPUN (semua BU), tanpa cek BU sama sekali —
        // jadi filter business_unit_id di query kendaraan sengaja DIHILANGKAN di sini.
        //
        // PERBAIKAN sebelumnya: hanya kendaraan berstatus "available" yang muncul di form.
        // Ini membuat driver yang SEDANG dalam perjalanan (kendaraan berstatus "in_use")
        // tidak bisa mengisi log BBM untuk kendaraan yang sedang dipakainya sendiri —
        // padahal itu justru skenario paling umum (isi bensin di tengah perjalanan).
        // Sekarang kendaraan dengan status "available" ATAU "in_use" sama-sama ditampilkan.
        // Kendaraan berstatus "maintenance" tetap disembunyikan karena memang tidak
        // seharusnya dipakai/diisi BBM.
        $vehicles = Vehicle::whereIn('status', ['available', 'in_use'])
            ->orderBy('plate_number')
            ->get();
        $driver = Auth::user()->driver;
        return view('drms.fuel_logs.create', compact('vehicles', 'driver'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:drms_vehicles,id',
            'filling_date' => 'required|date',
            'odometer_start' => 'required|integer|min:0',
            'fuel_liters' => 'required|numeric|min:0.01',
            'fuel_total_price' => 'required|numeric|min:0',
            'receipt_file' => 'nullable|image|max:5120',
            'notes' => 'nullable|string',
        ]);

        $validated['fuel_price_per_liter'] = $validated['fuel_liters'] > 0
            ? $validated['fuel_total_price'] / $validated['fuel_liters']
            : 0;
        unset($validated['fuel_total_price']);

        $driver = Auth::user()->driver;
        $validated['driver_id'] = $driver ? $driver->id : null;
        $validated['user_id'] = Auth::id();
        $validated['is_verified'] = 0;

        if ($request->hasFile('receipt_file')) {
            $validated['receipt_file'] = ImageHelper::compressAndStore($request->file('receipt_file'), 'fuel_receipts');
        }

        FuelLog::create($validated);
        return redirect()->route('drms.fuel-logs.index')->with('success', 'Log BBM berhasil disimpan.');
    }

    public function show($id)
    {
        $log = FuelLog::with('vehicle', 'driver', 'user', 'verifier')->findOrFail($id);
        return view('drms.fuel_logs.show', compact('log'));
    }

    public function edit($id)
    {
        $log = FuelLog::with('driver')->findOrFail($id);
        $buId = $this->getBusinessUnitId();
        // Sama seperti create(): kendaraan "available" atau "in_use" ditampilkan,
        // ditambah kendaraan yang sudah tersimpan di log ini apa pun statusnya
        // (misalnya sudah berubah jadi "maintenance" setelah log dibuat).
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))
            ->where(function ($q) use ($log) {
                $q->whereIn('status', ['available', 'in_use'])
                  ->orWhere('id', $log->vehicle_id); // tetap tampilkan kendaraan yang sudah dipilih di log ini
            })
            ->orderBy('plate_number')
            ->get();
        // Driver yang ditampilkan adalah driver ASLI pemilik log ini, bukan driver dari user yang sedang login
        // (yang mungkin admin dan tidak punya profil driver sama sekali).
        $driver = $log->driver;
        return view('drms.fuel_logs.edit', compact('log', 'vehicles', 'driver'));
    }

    public function update(Request $request, $id)
    {
        $log = FuelLog::findOrFail($id);
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:drms_vehicles,id',
            'filling_date' => 'required|date',
            'odometer_start' => 'required|integer|min:0',
            'fuel_liters' => 'required|numeric|min:0.01',
            'fuel_total_price' => 'required|numeric|min:0',
            'receipt_file' => 'nullable|image|max:5120',
            'notes' => 'nullable|string',
        ]);

        $validated['fuel_price_per_liter'] = $validated['fuel_liters'] > 0
            ? $validated['fuel_total_price'] / $validated['fuel_liters']
            : 0;
        unset($validated['fuel_total_price']);

        // PERBAIKAN: sebelumnya driver_id selalu ditimpa dengan driver milik user yang sedang
        // login (Auth::user()->driver). Ini membuat nama driver hilang setiap kali admin
        // (yang tidak punya profil driver) mengedit/menyetujui log milik driver lain.
        // Sekarang driver_id asli pada log dipertahankan kecuali yang mengedit adalah
        // driver itu sendiri (mengedit log miliknya sendiri).
        $editorDriver = Auth::user()->driver;
        if ($editorDriver && $editorDriver->id === $log->driver_id) {
            $validated['driver_id'] = $editorDriver->id;
        }
        // Jika bukan driver pemilik log (mis. admin), driver_id yang sudah tersimpan tidak diubah.

        if ($request->hasFile('receipt_file')) {
            if ($log->receipt_file) ImageHelper::deleteImage($log->receipt_file);
            $validated['receipt_file'] = ImageHelper::compressAndStore($request->file('receipt_file'), 'fuel_receipts');
        }
        $log->update($validated);
        return redirect()->route('drms.fuel-logs.index')->with('success', 'Log BBM diperbarui.');
    }

    public function verify(Request $request, $id)
    {
        $log = FuelLog::findOrFail($id);
        $log->is_verified = 1;
        $log->verified_by = Auth::id();
        $log->verified_at = now();
        $log->save();
        return redirect()->route('drms.fuel-logs.index')->with('success', 'Log BBM diverifikasi.');
    }

    public function destroy($id)
    {
        $log = FuelLog::findOrFail($id);
        if ($log->receipt_file) ImageHelper::deleteImage($log->receipt_file);
        $log->delete();
        return redirect()->route('drms.fuel-logs.index')->with('success', 'Log BBM dihapus.');
    }

    public function analytics(Request $request)
    {
        $buId = $this->getBusinessUnitId();

        $query = FuelLog::with('vehicle')
            ->where('is_verified', 1)
            ->when($buId, fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $buId)));

        // Filter per kendaraan
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        // Filter periode (bulan ini / bulan lalu / custom range)
        if ($request->filled('date_from')) {
            $query->whereDate('filling_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('filling_date', '<=', $request->date_to);
        }

        $logs = $query->orderBy('vehicle_id')->orderBy('filling_date')->get();

        $grouped = $logs->groupBy('vehicle_id');
        $result = [];
        foreach ($grouped as $vehicleId => $items) {
            $detail = $this->buildVehicleFillDetail($items);
            if (!$detail) continue;
            $vehicle = $detail['vehicle'];

            // Total liter/kWh & biaya yang benar-benar dibeli pada periode ini
            $totalLiters = $items->sum('fuel_liters');
            $totalCost = $items->sum('total_cost');
            $count = $items->count();
            $totalDistance = (int) collect($detail['rows'])->sum('distance');

            // Efisiensi aktual = rata-rata efisiensi per-interval (pakai baris riwayat
            // yang sama dengan yang ditampilkan di tabel detail, biar konsisten).
            $validRows = collect($detail['rows'])->filter(fn ($r) => $r['distance'] !== null && $r['efficiency'] !== null);
            $actualEfficiency = $validRows->isNotEmpty() ? round($validRows->avg('efficiency'), 2) : null;

            // ===== Skema standar efisiensi =====
            // Mobil BBM  : 8 km/L, Mobil Listrik : 6 km/kWh.
            // Deviasi (%) = seberapa jauh efisiensi aktual di BAWAH standar:
            //   - deviasi > 10%  -> warning MERAH (boros signifikan)
            //   - deviasi > 5%   -> warning KUNING (mulai boros)
            //   - deviasi <= 5%  -> normal (termasuk yang lebih irit dari standar)
            $efficiencyStandard = $detail['standard'];
            $deviationPercent = null;
            $warningLevel = null;
            if ($actualEfficiency !== null) {
                $deviationPercent = round((($efficiencyStandard - $actualEfficiency) / $efficiencyStandard) * 100, 1);
                if ($deviationPercent > 10) {
                    $warningLevel = 'red';
                } elseif ($deviationPercent > 5) {
                    $warningLevel = 'yellow';
                } else {
                    $warningLevel = 'normal';
                }
            }

            // Baris pengisian PALING TERAKHIR -- ditampilkan langsung di tabel ringkasan
            // supaya orang gak perlu klik ke kendaraan itu dulu buat lihat "sebelum vs sekarang".
            $lastRow = collect($detail['rows'])->last();

            $result[] = [
                'vehicle_id' => $vehicleId,
                'plate_number' => $vehicle->plate_number,
                'fuel_type' => $vehicle->fuel_type,
                'unit_label' => $detail['unit_label'],
                'fuel_unit_label' => $detail['fuel_unit_label'],
                'total_liters' => $totalLiters,
                'total_cost' => $totalCost,
                'total_distance' => $totalDistance,
                'count' => $count,
                'efficiency_standard' => $efficiencyStandard,
                'actual_efficiency' => $actualEfficiency,
                'deviation_percent' => $deviationPercent,
                'warning_level' => $warningLevel,
                // Pengisian terakhir (sebelum vs sekarang), langsung dari riwayat
                'last_fill_date' => $lastRow['current_date'] ?? null,
                'last_interval_distance' => $lastRow['distance'] ?? null,
                'last_interval_efficiency' => $lastRow['efficiency'] ?? null,
                'last_interval_warning' => $lastRow['warning_level'] ?? null,
                // Estimasi ke depan, langsung tampil tanpa perlu klik
                'estimate' => $detail['estimate'],
            ];
        }
        usort($result, function ($a, $b) {
            if ($a['actual_efficiency'] === null) return 1;
            if ($b['actual_efficiency'] === null) return -1;
            return $a['actual_efficiency'] <=> $b['actual_efficiency'];
        });

        // Ringkasan total keseluruhan untuk periode/filter yang sedang aktif
        $summary = [
            'total_liters'   => $logs->sum('fuel_liters'),
            'total_cost'     => $logs->sum('total_cost'),
            'total_distance' => array_sum(array_column($result, 'total_distance')),
            'count'          => $logs->count(),
            'warning_yellow_count' => count(array_filter($result, fn ($r) => $r['warning_level'] === 'yellow')),
            'warning_red_count'    => count(array_filter($result, fn ($r) => $r['warning_level'] === 'red')),
        ];

        // Daftar kendaraan untuk dropdown filter
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))
            ->orderBy('plate_number')
            ->get();

        // ===== Detail per pengisian (bukan cuma rata-rata) =====
        // Kalau admin sudah memfilter ke SATU kendaraan, tampilkan juga riwayat
        // pengisian sebelum vs sekarang satu-satu (bukan cuma dirangkum jadi
        // satu baris rata-rata), plus estimasi ke depan berdasarkan pengisian
        // yang paling baru — seolah-olah "kalau isi sekarang, kira-kira sampai
        // kapan/berapa jauh lagi & isi berikutnya kira-kira berapa".
        $vehicleDetail = null;
        if ($request->filled('vehicle_id') && $logs->isNotEmpty()) {
            $vehicleDetail = $this->buildVehicleFillDetail($logs);
        }

        return view('drms.fuel_logs.analytics', compact('result', 'summary', 'vehicles', 'vehicleDetail'));
    }

    /**
     * Bangun riwayat pengisian per-pasangan (pengisian sebelumnya -> pengisian
     * sekarang) untuk SATU kendaraan, beserta estimasi ke depan berdasarkan
     * pengisian paling baru.
     *
     * @param  \Illuminate\Support\Collection  $items  FuelLog milik SATU kendaraan, urut tanggal (lama -> baru)
     */
    private function buildVehicleFillDetail($items)
    {
        $vehicle = $items->first()->vehicle ?? null;
        if (!$vehicle) return null;

        $isListrik = ($vehicle->fuel_type === 'Listrik');
        $standard = $isListrik ? 6 : 8;
        $unitLabel = $isListrik ? 'km/kWh' : 'km/L';
        $fuelUnitLabel = $isListrik ? 'kWh' : 'Liter';

        // ----- Riwayat per pasangan pengisian (sebelum vs sekarang) -----
        $rows = [];
        $prev = null;
        foreach ($items as $item) {
            $distance = null;
            $efficiency = null;
            $deviationPercent = null;
            $warningLevel = null;

            if ($prev !== null && $item->odometer_start > $prev->odometer_start) {
                $distance = $item->odometer_start - $prev->odometer_start;
                if ($item->fuel_liters > 0) {
                    $efficiency = round($distance / $item->fuel_liters, 2);
                    $deviationPercent = round((($standard - $efficiency) / $standard) * 100, 1);
                    if ($deviationPercent > 10) {
                        $warningLevel = 'red';
                    } elseif ($deviationPercent > 5) {
                        $warningLevel = 'yellow';
                    } else {
                        $warningLevel = 'normal';
                    }
                }
            }

            $rows[] = [
                'previous_date'     => $prev->filling_date ?? null,
                'previous_odometer' => $prev->odometer_start ?? null,
                'current_date'      => $item->filling_date,
                'current_odometer'  => $item->odometer_start,
                'liters'            => $item->fuel_liters,
                'cost'              => $item->total_cost,
                'distance'          => $distance,
                'efficiency'        => $efficiency,
                'deviation_percent' => $deviationPercent,
                'warning_level'     => $warningLevel,
            ];

            $prev = $item;
        }

        // ----- Estimasi ke depan, dianggap "kalau isi sekarang" (dari pengisian TERAKHIR) -----
        // Efisiensi acuan pakai RATA-RATA dari seluruh interval yang punya jarak
        // pembanding (bukan cuma interval terakhir saja), supaya estimasinya tidak
        // terlalu liar kalau baru ada 1-2 riwayat pengisian.
        $validRows = collect($rows)->filter(fn ($r) => $r['distance'] !== null && $r['efficiency'] !== null);
        $lastFill = $items->last();

        $estimate = null;
        if ($lastFill && $validRows->isNotEmpty()) {
            $avgEfficiency = round($validRows->avg('efficiency'), 2);
            $avgLitersPerFill = round($items->avg('fuel_liters'), 2);

            // Rata-rata jarak hari antar pengisian, dari seluruh riwayat tanggal.
            $dateDiffs = [];
            $prevDate = null;
            foreach ($items as $item) {
                if ($prevDate !== null && $item->filling_date) {
                    $dateDiffs[] = $prevDate->diffInDays($item->filling_date);
                }
                $prevDate = $item->filling_date;
            }
            $avgDaysBetweenFills = !empty($dateDiffs) ? round(array_sum($dateDiffs) / count($dateDiffs), 1) : null;

            $estimate = [
                'based_on_date'          => $lastFill->filling_date,
                'based_on_liters'        => $lastFill->fuel_liters,
                'avg_efficiency'         => $avgEfficiency,
                // Estimasi jarak yang bisa ditempuh dari volume pengisian TERAKHIR ini,
                // pakai rata-rata efisiensi historisnya.
                'estimated_range_km'     => round($avgEfficiency * $lastFill->fuel_liters, 0),
                'avg_days_between_fills' => $avgDaysBetweenFills,
                'estimated_next_fill_date' => $avgDaysBetweenFills
                    ? \Carbon\Carbon::parse($lastFill->filling_date)->addDays($avgDaysBetweenFills)
                    : null,
                'estimated_next_liters'  => $avgLitersPerFill,
                // Estimasi biaya isi berikutnya pakai harga per liter/kWh dari pengisian terakhir
                // (asumsi harga relatif stabil dalam waktu dekat).
                'estimated_next_cost'    => round($avgLitersPerFill * $lastFill->fuel_price_per_liter, 0),
            ];
        }

        return [
            'vehicle'        => $vehicle,
            'unit_label'     => $unitLabel,
            'fuel_unit_label'=> $fuelUnitLabel,
            'standard'       => $standard,
            'rows'           => $rows,
            'estimate'       => $estimate,
        ];
    }
}