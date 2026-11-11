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

    /**
     * ID kendaraan khusus yang boleh dilihat & diverifikasi user ini walau beda BU
     * (tabel drms_log_verifier_vehicles). Superadmin tidak perlu (sudah lihat semua).
     */
    private function extraVehicleIds(): array
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin()) return [];
        return \App\Support\DrmsLogVerifier::vehicleIds($user);
    }

    /**
     * Batasi query FuelLog ke BU user, ditambah kendaraan khusus (kalau ada).
     */
    private function limitToScope($query, $buId)
    {
        if (!$buId) return $query;
        $extra = $this->extraVehicleIds();
        return $query->where(function ($w) use ($buId, $extra) {
            $w->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
            if (!empty($extra)) {
                $w->orWhereIn('vehicle_id', $extra);
            }
        });
    }

    /**
     * Business Unit efektif untuk filter halaman list/analitik.
     * - Superadmin: ikut filter business_unit_id di request (kosong = semua BU).
     * - User biasa: selalu BU miliknya sendiri (input request diabaikan).
     */
    private function effectiveBusinessUnitId(Request $request)
    {
        $buId = $this->getBusinessUnitId();
        if ($buId !== null) {
            return $buId;
        }
        return $request->filled('business_unit_id') ? (int) $request->business_unit_id : null;
    }

    /**
     * Daftar BU untuk dropdown filter (superadmin: semua; lainnya: BU sendiri saja, terkunci).
     */
    private function businessUnitOptions($lockedBuId)
    {
        return $lockedBuId !== null
            ? \App\Models\BisnisUnit::where('id_bisnis_unit', $lockedBuId)->get()
            : \App\Models\BisnisUnit::orderBy('nama_bisnis_unit')->get();
    }

    public function index(Request $request)
    {
        $lockedBuId = $this->getBusinessUnitId();           // null = superadmin
        $buId = $this->effectiveBusinessUnitId($request);
        $query = FuelLog::with('vehicle', 'driver', 'user', 'verifier');
        $this->limitToScope($query, $buId);   // BU sendiri + kendaraan khusus (kalau ada)
        if ($request->filled('date_from')) {
            $query->whereDate('filling_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('filling_date', '<=', $request->date_to);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('status')) {
            if ($request->status === 'draft') {
                $query->where('is_draft', 1);
            } elseif ($request->status === 'verified') {
                $query->where('is_draft', 0)->where('is_verified', 1);
            } else { // pending = sudah final tapi belum diverifikasi
                $query->where('is_draft', 0)->where('is_verified', 0);
            }
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
        // Draft TIDAK ikut dihitung di statistik (belum ada liter/harga), dihitung terpisah.
        $draftCount    = (clone $query)->where('is_draft', 1)->count();
        $totalLogs     = (clone $query)->where('is_draft', 0)->count();
        $verifiedCount = (clone $query)->where('is_draft', 0)->where('is_verified', 1)->count();
        $pendingCount  = (clone $query)->where('is_draft', 0)->where('is_verified', 0)->count();

        // Liter (BBM) dan kWh (Listrik) dipisah, bukan digabung jadi satu angka —
        // karena satuannya beda, jumlahin keduanya jadi satu tidak ada artinya.
        // Biayanya (Rp) juga dipisah per kategori, biar tiap kartu nampilin nominal
        // yang benar-benar sesuai isinya (bukan total gabungan semua log).
        // PENTING: fuel_type di kendaraan bisa NULL (belum diisi) — di SQL,
        // "fuel_type != 'Listrik'" akan SKIP baris yang NULL (NULL != 'Listrik' = NULL,
        // bukan true), jadi harus eksplisit ikutkan whereNull juga supaya kendaraan
        // yang fuel_type-nya belum diisi tetap terhitung sebagai BBM (default), bukan
        // hilang dari kedua total.
        $bbmQuery = (clone $query)->where('is_draft', 0)->whereHas('vehicle', function ($q) {
            $q->where('fuel_type', '!=', 'Listrik')->orWhereNull('fuel_type');
        });
        $totalLiters = (clone $bbmQuery)->sum('fuel_liters');
        $totalCostBbm = (clone $bbmQuery)->sum('total_cost');

        $listrikQuery = (clone $query)->where('is_draft', 0)->whereHas('vehicle', fn($q) => $q->where('fuel_type', 'Listrik'));
        $totalKwh = (clone $listrikQuery)->sum('fuel_liters');
        $totalCostListrik = (clone $listrikQuery)->sum('total_cost');

        $logs = $query->latest()->paginate(20)->appends($request->query());
        $extraVehicleIds = $this->extraVehicleIds();
        $vehicles = Vehicle::when($buId, function ($q) use ($buId, $extraVehicleIds) {
            $q->where(function ($w) use ($buId, $extraVehicleIds) {
                $w->where('business_unit_id', $buId);
                if (!empty($extraVehicleIds)) {
                    $w->orWhereIn('id', $extraVehicleIds);
                }
            });
        })->get();
        $businessUnits = $this->businessUnitOptions($lockedBuId);
        $filterBusinessUnitId = $buId;
        return view('drms.fuel_logs.index', compact(
            'logs', 'vehicles', 'month', 'totalLogs', 'verifiedCount', 'pendingCount', 'draftCount',
            'totalLiters', 'totalCostBbm', 'totalKwh', 'totalCostListrik',
            'businessUnits', 'filterBusinessUnitId'
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

    /**
     * Aturan validasi foto + data pengisian, tergantung mode simpan.
     *
     * - Mode DRAFT  : cukup kendaraan, tanggal, odometer, dan foto dashboard SEBELUM pengisian.
     *                 Liter, harga, foto sesudah, dan struk boleh kosong (driver masih antri/mengisi).
     * - Mode SUBMIT : semua wajib (liter, harga, foto dashboard sebelum + sesudah, struk).
     *
     * @param  FuelLog|null $log  log yang sedang diedit (null = input baru). Foto yang sudah
     *                            tersimpan di log tidak wajib diupload ulang.
     */
    private function rulesFor(bool $isDraft, ?FuelLog $log = null): array
    {
        // Foto wajib hanya untuk input BARU atau saat draft diselesaikan. Edit log final lama
        // (termasuk log sebelum fitur foto dashboard ada) tidak dipaksa upload ulang.
        $strictPhotos = !$isDraft && ($log === null || $log->is_draft);

        $photo = function (string $field) use ($strictPhotos, $log) {
            $alreadyHas = $log && $log->$field;
            return ($strictPhotos && !$alreadyHas)
                ? 'required|image|max:5120'
                : 'nullable|image|max:5120';
        };

        return [
            'vehicle_id'     => 'required|exists:drms_vehicles,id',
            'filling_date'   => 'required|date',
            'odometer_start' => 'required|integer|min:0',
            'fuel_liters'    => $isDraft ? 'nullable|numeric|min:0.01' : 'required|numeric|min:0.01',
            'fuel_total_price' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            // Foto sebelum pengisian: wajib saat input baru (draft maupun kirim) dan TIDAK bisa
            // diubah setelah tersimpan, jadi tidak divalidasi/diproses sama sekali saat edit.
            'photo_dashboard_before' => $log === null ? 'required|image|max:5120' : 'prohibited',
            'photo_dashboard_after'  => $photo('photo_dashboard_after'),
            'receipt_file'           => $photo('receipt_file'),
            'notes'          => 'nullable|string',
        ];
    }

    private function rulesMessages(): array
    {
        return [
            'photo_dashboard_before.required' => 'Foto Km dan Indikator BBM/Baterai Sebelum Pengisian wajib diisi.',
            'photo_dashboard_after.required'  => 'Foto Km dan Indikator BBM/Baterai Setelah Pengisian wajib diisi.',
            'receipt_file.required'           => 'Foto struk pengisian wajib diisi.',
            'fuel_liters.required'            => 'Jumlah liter/kWh wajib diisi.',
            'fuel_total_price.required'       => 'Harga total wajib diisi.',
        ];
    }

    /**
     * Isi total_cost & harga per liter dari input form. Kosong (draft) -> null.
     */
    private function applyCost(array $validated): array
    {
        $price  = $validated['fuel_total_price'] ?? null;
        $liters = $validated['fuel_liters'] ?? null;

        // Total biaya = angka di nota, disimpan APA ADANYA (tidak dihitung dari liter x harga).
        // Harga per liter hanya info turunan, tidak dipakai untuk menghitung biaya.
        $validated['total_cost'] = $price;
        $validated['fuel_price_per_liter'] = ($price !== null && $liters !== null && $liters > 0)
            ? round($price / $liters, 2)
            : null;
        unset($validated['fuel_total_price']);
        return $validated;
    }

    public function store(Request $request)
    {
        // save_mode: "draft" (simpan dulu, lengkapi setelah selesai mengisi) atau "submit".
        $isDraft = $request->input('save_mode') === 'draft';

        $validated = $request->validate($this->rulesFor($isDraft), $this->rulesMessages());
        $validated = $this->applyCost($validated);

        $driver = Auth::user()->driver;
        $validated['driver_id'] = $driver ? $driver->id : null;
        $validated['user_id'] = Auth::id();
        $validated['is_verified'] = 0;
        $validated['is_draft'] = $isDraft ? 1 : 0;

        $validated['photo_dashboard_before'] = ImageHelper::compressAndStore($request->file('photo_dashboard_before'), 'fuel_dashboards');
        foreach (['photo_dashboard_after' => 'fuel_dashboards', 'receipt_file' => 'fuel_receipts'] as $field => $dir) {
            if ($request->hasFile($field)) {
                $validated[$field] = ImageHelper::compressAndStore($request->file($field), $dir);
            } else {
                unset($validated[$field]);
            }
        }

        FuelLog::create($validated);

        return redirect()->route('drms.fuel-logs.index')->with('success', $isDraft
            ? 'Draft tersimpan. Setelah selesai mengisi, buka draft ini lalu tekan Lengkapi.'
            : 'Log BBM berhasil disimpan.');
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

        // Log yang masih DRAFT: boleh disimpan sebagai draft lagi (save_mode=draft) atau
        // diselesaikan (save_mode=submit -> semua data + foto sesudah + struk wajib).
        // Log yang sudah final: selalu divalidasi penuh seperti sebelumnya.
        $isDraft = $log->is_draft && $request->input('save_mode') === 'draft';

        $validated = $request->validate($this->rulesFor($isDraft, $log), $this->rulesMessages());
        $validated = $this->applyCost($validated);

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

        $photoDirs = [
            'photo_dashboard_after'  => 'fuel_dashboards',
            'receipt_file'           => 'fuel_receipts',
        ];
        foreach ($photoDirs as $field => $dir) {
            if ($request->hasFile($field)) {
                if ($log->$field) ImageHelper::deleteImage($log->$field);
                $validated[$field] = ImageHelper::compressAndStore($request->file($field), $dir);
            } else {
                unset($validated[$field]); // tidak diganti -> pertahankan foto lama
            }
        }

        // Draft yang diselesaikan -> jadi log final (masuk analitik & bisa diverifikasi).
        if ($log->is_draft && !$isDraft) {
            $validated['is_draft'] = 0;
        }

        $log->update($validated);

        return redirect()->route('drms.fuel-logs.index')->with('success', $isDraft
            ? 'Draft diperbarui.'
            : ($log->wasChanged('is_draft') ? 'Log BBM berhasil dilengkapi.' : 'Log BBM diperbarui.'));
    }

    public function verify(Request $request, $id)
    {
        $log = FuelLog::with('vehicle')->findOrFail($id);

        // Draft belum lengkap (liter, harga, foto sesudah, struk) -> belum boleh diverifikasi.
        if ($log->is_draft) {
            return redirect()->route('drms.fuel-logs.index')
                ->with('error', 'Log masih berstatus draft, belum bisa diverifikasi.');
        }

        // null = superadmin. Admin biasa hanya boleh log kendaraan BU-nya,
        // atau kendaraan khusus yang diberikan lewat drms_log_verifier_vehicles.
        $buId = $this->getBusinessUnitId();
        if ($buId !== null
            && ($log->vehicle->business_unit_id ?? null) != $buId
            && !\App\Support\DrmsLogVerifier::canVerifyVehicle(Auth::user(), $log->vehicle_id)) {
            abort(403, 'Anda tidak memiliki akses ke log ini.');
        }

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
        if ($log->photo_dashboard_before) ImageHelper::deleteImage($log->photo_dashboard_before);
        if ($log->photo_dashboard_after) ImageHelper::deleteImage($log->photo_dashboard_after);
        $log->delete();
        return redirect()->route('drms.fuel-logs.index')->with('success', 'Log BBM dihapus.');
    }

    public function analytics(Request $request)
    {
        $lockedBuId = $this->getBusinessUnitId();           // null = superadmin
        $buId = $this->effectiveBusinessUnitId($request);

        // Default periode: 3 bulan terakhir sampai hari ini, kalau tidak ada filter tanggal sama sekali.
        // Pilih "Semua Periode" (period=all) atau kosongkan kedua tanggal lalu Tampilkan untuk melihat semuanya.
        $isDefaultPeriod = false;
        if (!$request->has('date_from') && !$request->has('date_to') && $request->query('period') !== 'all') {
            $request->merge([
                'date_from' => now()->subMonthsNoOverflow(3)->format('Y-m-d'),
                'date_to'   => now()->format('Y-m-d'),
            ]);
            $isDefaultPeriod = true;
        }

        // Semua log masuk analitik, termasuk yang BELUM diverifikasi (tidak ada filter is_verified).
        // Draft tidak ikut analitik (belum ada liter/harga; odometernya juga belum final).
        $query = FuelLog::with('vehicle')
            ->where('is_draft', 0)
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

        // Kalau filter "Dari Tanggal" dipakai, pengisian PERTAMA di dalam periode tidak lagi
        // dianggap "pengisian pertama" (tanpa pembanding), tapi dibandingkan dengan pengisian
        // TERAKHIR sebelum periode (per kendaraan) sebagai angka awal.
        $baselines = $this->baselineFillsBefore($logs, $request->date_from, $buId);

        $grouped = $logs->groupBy('vehicle_id');
        $result = [];
        foreach ($grouped as $vehicleId => $items) {
            $detail = $this->buildVehicleFillDetail($items, $baselines[$vehicleId] ?? null);
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
                // Seluruh baris riwayat (sesuai filter) -- dipakai popup "Lihat hitungan" rata-rata
                'rows' => $detail['rows'],
                // Estimasi ke depan, langsung tampil tanpa perlu klik
                'estimate' => $detail['estimate'],
            ];
        }
        // Urutkan: kendaraan dengan pengisian PALING BARU di atas.
        // Yang tidak punya tanggal isi terakhir ditaruh paling bawah.
        usort($result, function ($a, $b) {
            $da = $a['last_fill_date'] ? \Carbon\Carbon::parse($a['last_fill_date'])->timestamp : null;
            $db = $b['last_fill_date'] ? \Carbon\Carbon::parse($b['last_fill_date'])->timestamp : null;

            if ($da === null && $db === null) return strcmp((string) $a['plate_number'], (string) $b['plate_number']);
            if ($da === null) return 1;
            if ($db === null) return -1;

            return $db <=> $da; // terbaru dulu
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
            $vehicleDetail = $this->buildVehicleFillDetail($logs, $baselines[(int) $request->vehicle_id] ?? null);
        }

        $businessUnits = $this->businessUnitOptions($lockedBuId);
        $filterBusinessUnitId = $buId;

        return view('drms.fuel_logs.analytics', compact(
            'result', 'summary', 'vehicles', 'vehicleDetail', 'businessUnits', 'filterBusinessUnitId',
            'isDefaultPeriod'
        ));
    }

    /**
     * Pengisian TERAKHIR (verified maupun pending) sebelum $dateFrom untuk tiap kendaraan di $logs.
     * Dipakai sebagai pembanding (odometer awal) untuk pengisian pertama di dalam periode filter.
     * Mengikuti scope BU yang sama dengan query analitik. 2 query saja, tidak per kendaraan.
     *
     * @return array<int, FuelLog>  key = vehicle_id
     */
    private function baselineFillsBefore($logs, $dateFrom, $buId): array
    {
        if (!$dateFrom || $logs->isEmpty()) return [];

        $vehicleIds = $logs->pluck('vehicle_id')->unique()->values()->all();

        $base = fn () => FuelLog::whereIn('vehicle_id', $vehicleIds)
            ->where('is_draft', 0)
            ->whereDate('filling_date', '<', $dateFrom)
            ->when($buId, fn ($q) => $q->whereHas('vehicle', fn ($sq) => $sq->where('business_unit_id', $buId)));

        // Tanggal pengisian terakhir sebelum periode, per kendaraan
        $lastDates = $base()
            ->selectRaw('vehicle_id, MAX(filling_date) as last_date')
            ->groupBy('vehicle_id')
            ->pluck('last_date', 'vehicle_id');
        if ($lastDates->isEmpty()) return [];

        $candidates = $base()->where(function ($w) use ($lastDates) {
            foreach ($lastDates as $vehicleId => $date) {
                $w->orWhere(fn ($q) => $q->where('vehicle_id', $vehicleId)->whereDate('filling_date', $date));
            }
        })->get();

        // Kalau ada beberapa isi di hari yang sama, ambil yang odometernya paling tinggi
        return $candidates->groupBy('vehicle_id')
            ->map(fn ($g) => $g->sortBy([['odometer_start', 'desc'], ['id', 'desc']])->first())
            ->all();
    }

    /**
     * Bangun riwayat pengisian per-pasangan (pengisian sebelumnya -> pengisian
     * sekarang) untuk SATU kendaraan, beserta estimasi ke depan berdasarkan
     * pengisian paling baru.
     *
     * @param  \Illuminate\Support\Collection  $items  FuelLog milik SATU kendaraan, urut tanggal (lama -> baru)
     * @param  FuelLog|null  $baseline  pengisian terakhir SEBELUM periode filter (kalau ada), jadi
     *                                  pembanding untuk pengisian pertama di periode
     */
    private function buildVehicleFillDetail($items, $baseline = null)
    {
        $vehicle = $items->first()->vehicle ?? null;
        if (!$vehicle) return null;

        $isListrik = ($vehicle->fuel_type === 'Listrik');
        $standard = $isListrik ? 6 : 8;
        $unitLabel = $isListrik ? 'km/kWh' : 'km/L';
        $fuelUnitLabel = $isListrik ? 'kWh' : 'Liter';

        // ----- Riwayat per pasangan pengisian (sebelum vs sekarang) -----
        $rows = [];
        $prev = $baseline;
        $prevIsBaseline = $baseline !== null;
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
                // true = pembanding berasal dari pengisian SEBELUM periode filter
                'previous_outside'  => $prevIsBaseline,
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
            $prevIsBaseline = false;
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
            $prevDate = $baseline ? $baseline->filling_date : null;
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