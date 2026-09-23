<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Driver;
use App\Models\Drms\DriverRequest;
use App\Models\BisnisUnit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    /**
     * Display a listing of drivers with filters.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Driver::with('businessUnit');

        // Jika bukan superadmin, batasi berdasarkan business unit
        if (!$user->isDrmsSuperAdmin()) {
            $businessUnitId = $user->drmsProfile->business_unit_id ?? null;
            if (!$businessUnitId) {
                abort(403, 'Anda tidak memiliki unit bisnis.');
            }
            $query->where('business_unit_id', $businessUnitId);
        }

        // Filter pencarian (nama atau telepon)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', $search)
                  ->orWhere('phone', 'LIKE', $search);
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter business unit (khusus superadmin)
        if ($user->isDrmsSuperAdmin() && $request->filled('business_unit_id')) {
            $query->where('business_unit_id', $request->business_unit_id);
        }

        $drivers = $query->latest()->paginate(20)->appends($request->query());

        // Daftar nama driver (tanpa filter search/status) untuk rekomendasi di kotak pencarian.
        $driverNamesQuery = Driver::query();
        if (!$user->isDrmsSuperAdmin()) {
            $driverNamesQuery->where('business_unit_id', $user->drmsProfile->business_unit_id ?? null);
        } elseif ($request->filled('business_unit_id')) {
            $driverNamesQuery->where('business_unit_id', $request->business_unit_id);
        }
        $driverNames = $driverNamesQuery->orderBy('name')->pluck('name');

        // Ambil daftar business unit untuk dropdown filter (khusus superadmin)
        $businessUnits = [];
        if ($user->isDrmsSuperAdmin()) {
            $businessUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();
        }

        return view('drms.drivers.index', compact('drivers', 'businessUnits', 'driverNames'));
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin() && !$user->drmsProfile->business_unit_id) {
            abort(403, 'Anda tidak memiliki unit bisnis.');
        }
        $accounts = $this->driverCandidates($user);
        return view('drms.drivers.create', compact('accounts'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Nama driver TIDAK lagi diketik bebas: dipilih dari daftar akun terdaftar (user_id),
        // lalu nama, username, dan business unit diisi otomatis dari akun tersebut.
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'phone'   => 'nullable|string|max:20',
            'status'  => 'required|in:available,on_trip,off_duty',
        ], [
            'user_id.required' => 'Pilih nama driver dari daftar.',
            'user_id.exists'   => 'Nama driver yang dipilih tidak valid.',
        ]);

        $account = User::with('drmsProfile')->findOrFail($data['user_id']);
        $this->assertAccountUsable($user, $account);

        $payload = [
            'name'     => $account->name,
            'username' => $account->username,
            'phone'    => $data['phone'] ?? null,
            'status'   => $data['status'],
        ];

        if ($user->isDrmsSuperAdmin()) {
            // Superadmin: business unit mengikuti profil akun yang dipilih
            // (fallback ke input business_unit_id kalau profil akun belum punya BU).
            $payload['business_unit_id'] = $account->drmsProfile->business_unit_id
                ?? $request->business_unit_id
                ?? null;
        } else {
            $payload['business_unit_id'] = $user->drmsProfile->business_unit_id;
        }

        Driver::create($payload);

        return redirect()->route('drms.drivers.index')
            ->with('success', 'Driver berhasil ditambahkan.');
    }

    public function edit(Driver $driver)
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin()) {
            $this->checkBusinessUnit($driver);
        }
        $accounts = $this->driverCandidates($user, $driver->username);
        return view('drms.drivers.edit', compact('driver', 'accounts'));
    }

    public function update(Request $request, Driver $driver)
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin()) {
            $this->checkBusinessUnit($driver);
        }

        // Nama tidak lagi free text. Kalau admin memilih akun baru di dropdown (user_id),
        // nama + username driver mengikuti akun itu. Kalau tidak diubah, nama lama dipertahankan.
        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'phone'   => 'nullable|string|max:20',
            'status'  => 'required|in:available,on_trip,off_duty',
        ]);

        $payload = [
            'phone'  => $data['phone'] ?? null,
            'status' => $data['status'],
        ];

        if (!empty($data['user_id'])) {
            $account = User::with('drmsProfile')->findOrFail($data['user_id']);
            $this->assertAccountUsable($user, $account, $driver);
            $payload['name']     = $account->name;
            $payload['username'] = $account->username;
        }

        $driver->update($payload);

        return redirect()->route('drms.drivers.index')
            ->with('success', 'Driver berhasil diperbarui.');
    }

    public function destroy(Driver $driver)
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin()) {
            $this->checkBusinessUnit($driver);
        }
        $driver->delete();
        return redirect()->route('drms.drivers.index')
            ->with('success', 'Driver dihapus.');
    }

    public function schedule(Request $request)
{
    $user = Auth::user();
    $businessUnitId = null;

    // PERBAIKAN: jadwal sebelumnya per-tanggal (satu hari). Sekarang diubah
    // jadi per-BULAN — filter "date" diganti "month" (format Y-m), lalu di
    // view ditampilkan sebagai satu tabel matriks: baris = driver, kolom =
    // tanggal 1..akhir bulan, dan jadwal per hari disusun ke bawah (stack)
    // di dalam sel tanggal tersebut.
    $month = $request->get('month', now()->format('Y-m'));
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = now()->format('Y-m');
    }
    $monthStart = \Carbon\Carbon::createFromFormat('Y-m-d', $month . '-01')->startOfMonth();
    $monthEnd = $monthStart->copy()->endOfMonth();

    $searchDriver = $request->get('search');
    $statusFilter = $request->get('status'); // scheduled, on_trip, completed, all

    // Query driver
    $driverQuery = Driver::with('businessUnit');

    if ($user->isDrmsSuperAdmin()) {
        // Superadmin bisa lihat semua
        if ($request->filled('business_unit_id')) {
            $driverQuery->where('business_unit_id', $request->business_unit_id);
        }
    } else {
        $businessUnitId = $user->drmsProfile->business_unit_id ?? null;
        if (!$businessUnitId) {
            abort(403);
        }
        $driverQuery->where('business_unit_id', $businessUnitId);
    }

    // Daftar pilihan dropdown Driver: semua driver dalam cakupan BU (sebelum filter
    // Driver / Cari Driver diterapkan), supaya dropdown tetap lengkap setelah difilter.
    $driverOptions = (clone $driverQuery)->orderBy('name')->get(['id', 'name', 'business_unit_id']);

    // Filter Driver (dropdown, satu driver tertentu)
    $driverIdFilter = $request->get('driver_id');
    if ($driverIdFilter) {
        $driverQuery->where('id', $driverIdFilter);
    }

    // Cari Driver (kotak pencarian bebas, terpisah dari dropdown)
    if ($searchDriver) {
        $driverQuery->where('name', 'LIKE', '%' . $searchDriver . '%');
    }

    $drivers = $driverQuery->orderBy('name')->get();

    // Query requests untuk SATU BULAN PENUH (bukan satu tanggal lagi).
    // PERBAIKAN: sebelumnya cuma dicek usage_date-nya masuk bulan ini. Untuk trip
    // pulang-pergi (round_trip) yang punya return_date, itu bikin jadwal yang
    // sebenarnya berlangsung beberapa hari (mis. usage_date 28 Juli - return_date
    // 2 Agustus) tidak ikut muncul di bulan Agustus padahal masih berjalan.
    // Sekarang pakai kondisi OVERLAP: request ikut ditampilkan selama rentang
    // [usage_date .. effective_end_date] beririsan dengan rentang bulan yang dipilih,
    // dengan effective_end_date = return_date (kalau round_trip & diisi) atau usage_date.
    $requestQuery = DriverRequest::with('driver', 'requester', 'requester.drmsProfile.businessUnit')
        ->where('usage_date', '<=', $monthEnd->format('Y-m-d'))
        ->where(function ($q) use ($monthStart) {
            $q->where(function ($q2) use ($monthStart) {
                // Trip pulang-pergi: pakai return_date sebagai batas akhir
                $q2->where('trip_type', 'round_trip')
                   ->whereNotNull('return_date')
                   ->where('return_date', '>=', $monthStart->format('Y-m-d'));
            })->orWhere(function ($q2) use ($monthStart) {
                // Selain itu (sekali jalan, atau round_trip tanpa return_date): pakai usage_date
                $q2->where(function ($q3) {
                    $q3->where('trip_type', '!=', 'round_trip')->orWhereNull('return_date');
                })->where('usage_date', '>=', $monthStart->format('Y-m-d'));
            });
        })
        ->whereIn('status', ['approved_admin', 'completed']);

    if ($statusFilter && $statusFilter != 'all') {
        $now = now();
        if ($statusFilter == 'scheduled') {
            $requestQuery->where('status', 'approved_admin')
                ->where(function ($q) use ($now) {
                    // Terjadwal = tanggal di masa depan, ATAU hari ini tapi jam mulai belum lewat
                    $q->whereDate('usage_date', '>', $now->format('Y-m-d'))
                      ->orWhere(function ($q2) use ($now) {
                          $q2->whereDate('usage_date', '=', $now->format('Y-m-d'))
                             ->whereTime('start_time', '>', $now->format('H:i:s'));
                      });
                });
        } elseif ($statusFilter == 'on_trip') {
            // Dalam perjalanan hanya mungkin terjadi pada tanggal HARI INI,
            // dengan jam sekarang berada di antara start_time dan end_time.
            $requestQuery->where('status', 'approved_admin')
                ->whereDate('usage_date', '=', $now->format('Y-m-d'))
                ->whereTime('start_time', '<=', $now->format('H:i:s'))
                ->whereTime('end_time', '>', $now->format('H:i:s'));
        } elseif ($statusFilter == 'completed') {
            $requestQuery->where('status', 'completed');
        }
    }

    // Filter berdasarkan driver yang sudah dipilih (dropdown Driver atau Cari Driver)
    if ($searchDriver || $driverIdFilter) {
        $driverIds = $drivers->pluck('id')->toArray();
        $requestQuery->whereIn('driver_id', $driverIds);
    } else {
        // Jika tidak ada filter driver, tetap filter berdasarkan business unit
        if (!$user->isDrmsSuperAdmin()) {
            $requestQuery->whereHas('driver', function ($q) use ($businessUnitId) {
                $q->where('business_unit_id', $businessUnitId);
            });
        }
    }

    $allRequests = $requestQuery->orderBy('usage_date')->orderBy('start_time')->get();

    // Daftar tanggal (1..akhir bulan) untuk header kolom tabel
    $daysInMonth = [];
    $cursor = $monthStart->copy();
    while ($cursor->lte($monthEnd)) {
        $daysInMonth[] = $cursor->copy();
        $cursor->addDay();
    }
    $totalDays = count($daysInMonth);

    // PERBAIKAN: sebelumnya jadwal cuma "nempel" sebagai label di SATU sel tanggal
    // (usage_date), padahal request bisa berlangsung beberapa hari (trip pulang-pergi).
    // Sekarang tiap request dihitung rentang harinya (startIdx..endIdx, 0-based dari
    // awal bulan yang tampil), lalu di-render sebagai satu sel dengan colspan yang
    // membentang sepanjang rentang tanggalnya (mirip Gantt chart).
    //
    // Karena dalam satu baris tabel (<tr>) tidak bisa ada dua sel yang tumpang tindih,
    // request yang tanggalnya beririsan untuk driver yang sama dipisah ke "lane"
    // (baris tambahan) yang berbeda memakai algoritma interval partitioning standar:
    // taruh tiap request di lane pertama yang tidak bentrok, kalau semua bentrok
    // baru buka lane baru.
    $driverLanes = [];
    $requestsByDriver = $allRequests->groupBy('driver_id');

    foreach ($requestsByDriver as $driverId => $driverRequests) {
        $segments = [];
        foreach ($driverRequests as $req) {
            $effectiveEnd = ($req->trip_type === 'round_trip' && $req->return_date)
                ? $req->return_date->copy()
                : $req->usage_date->copy();
            if ($effectiveEnd->lt($req->usage_date)) {
                // Jaga-jaga kalau return_date ternyata lebih awal dari usage_date (data tidak konsisten)
                $effectiveEnd = $req->usage_date->copy();
            }

            $segStart = $req->usage_date->copy()->max($monthStart);
            $segEnd = $effectiveEnd->copy()->min($monthEnd);
            if ($segStart->gt($segEnd)) continue; // di luar rentang bulan yang tampil

            $startIdx = $monthStart->diffInDays($segStart);
            $endIdx = $monthStart->diffInDays($segEnd);

            $segments[] = [
                'startIdx' => $startIdx,
                'endIdx' => $endIdx,
                'request' => $req,
            ];
        }

        // Interval partitioning: urutkan berdasarkan startIdx, taruh di lane pertama yang kosong
        usort($segments, fn($a, $b) => $a['startIdx'] <=> $b['startIdx']);
        $lanes = []; // tiap lane: ['lastEnd' => int, 'segments' => [...]]
        foreach ($segments as $seg) {
            $placed = false;
            foreach ($lanes as &$lane) {
                if ($seg['startIdx'] > $lane['lastEnd']) {
                    $lane['segments'][] = $seg;
                    $lane['lastEnd'] = $seg['endIdx'];
                    $placed = true;
                    break;
                }
            }
            unset($lane);
            if (!$placed) {
                $lanes[] = ['lastEnd' => $seg['endIdx'], 'segments' => [$seg]];
            }
        }

        // Bangun deretan cell per lane (siap dirender): gap (kosong) + bar (colspan)
        $renderedLanes = [];
        foreach ($lanes as $lane) {
            $cells = [];
            $pointer = 0;
            foreach ($lane['segments'] as $seg) {
                if ($seg['startIdx'] > $pointer) {
                    $cells[] = ['type' => 'gap', 'colspan' => $seg['startIdx'] - $pointer];
                }
                $cells[] = [
                    'type' => 'req',
                    'colspan' => $seg['endIdx'] - $seg['startIdx'] + 1,
                    'request' => $seg['request'],
                ];
                $pointer = $seg['endIdx'] + 1;
            }
            if ($pointer < $totalDays) {
                $cells[] = ['type' => 'gap', 'colspan' => $totalDays - $pointer];
            }
            $renderedLanes[] = $cells;
        }
        if (empty($renderedLanes)) {
            $renderedLanes[] = [['type' => 'gap', 'colspan' => $totalDays]];
        }

        $driverLanes[$driverId] = $renderedLanes;
    }

    // Ambil daftar business unit untuk superadmin
    $businessUnits = [];
    if ($user->isDrmsSuperAdmin()) {
        $businessUnits = \App\Models\BisnisUnit::orderBy('nama_bisnis_unit')->get();
    }

    return view('drms.drivers.schedule', compact(
        'drivers', 'driverLanes', 'allRequests', 'daysInMonth', 'totalDays', 'month', 'monthStart', 'monthEnd',
        'searchDriver', 'statusFilter', 'businessUnits', 'user', 'driverOptions', 'driverIdFilter'
    ));
}

    /**
     * Daftar akun yang bisa dipilih di dropdown "Nama Driver": akun ber-username yang
     * BELUM terdaftar sebagai driver. Non-superadmin hanya melihat akun di business unit-nya.
     * $keepUsername = username driver yang sedang diedit (tetap muncul supaya bisa terpilih).
     */
    private function driverCandidates($authUser, $keepUsername = null)
    {
        $taken = Driver::whereNotNull('username')->pluck('username')
            ->reject(fn($u) => $keepUsername !== null && $u === $keepUsername)
            ->all();

        $query = User::whereNotNull('username')->where('username', '!=', '');

        if (!$authUser->isDrmsSuperAdmin()) {
            $buId = $authUser->drmsProfile->business_unit_id ?? null;
            $query->whereHas('drmsProfile', fn($q) => $q->where('business_unit_id', $buId));
        }

        if (!empty($taken)) {
            $query->whereNotIn('username', $taken);
        }

        return $query->orderBy('name')->get(['id', 'name', 'username']);
    }

    /**
     * Validasi sisi server untuk akun yang dipilih (dropdown bisa dimanipulasi dari browser).
     */
    private function assertAccountUsable($authUser, User $account, ?Driver $current = null)
    {
        if (empty($account->username)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'user_id' => 'Akun yang dipilih belum punya username, tidak bisa dijadikan driver.',
            ]);
        }

        if (!$authUser->isDrmsSuperAdmin()) {
            $myBu = $authUser->drmsProfile->business_unit_id ?? null;
            $accBu = $account->drmsProfile->business_unit_id ?? null;
            if (!$myBu || (int) $myBu !== (int) $accBu) {
                abort(403, 'Akun ini bukan bagian dari business unit Anda.');
            }
        }

        $exists = Driver::where('username', $account->username)
            ->when($current, fn($q) => $q->where('id', '!=', $current->id))
            ->exists();
        if ($exists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'user_id' => 'Akun ini sudah terdaftar sebagai driver.',
            ]);
        }
    }

    /**
     * Cek apakah driver milik business unit user yang sedang login (kecuali superadmin).
     */
    private function checkBusinessUnit(Driver $driver)
    {
        $userBusinessUnitId = Auth::user()->drmsProfile->business_unit_id ?? null;
        if (!$userBusinessUnitId || $driver->business_unit_id !== $userBusinessUnitId) {
            abort(403, 'Anda tidak memiliki akses ke driver ini.');
        }
    }
}