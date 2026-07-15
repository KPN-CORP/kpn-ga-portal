<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Driver;
use App\Models\Drms\DriverRequest;
use App\Models\BisnisUnit;
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

        // Ambil daftar business unit untuk dropdown filter (khusus superadmin)
        $businessUnits = [];
        if ($user->isDrmsSuperAdmin()) {
            $businessUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();
        }

        return view('drms.drivers.index', compact('drivers', 'businessUnits'));
    }

    public function create()
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin() && !$user->drmsProfile->business_unit_id) {
            abort(403, 'Anda tidak memiliki unit bisnis.');
        }
        return view('drms.drivers.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'nullable|string|max:20',
            'status' => 'required|in:available,on_trip,off_duty',
        ]);

        if ($user->isDrmsSuperAdmin()) {
            // Superadmin bisa memilih business_unit_id (tambahkan field di form jika diperlukan)
            // Untuk sederhananya, kita set null atau minta input. Agar aman, kita set null dulu.
            // Namun lebih baik tambahkan select business_unit di form untuk superadmin.
            // Untuk sekarang, jika tidak ada input, kita set null (artinya driver milik semua BU? Tidak ideal)
            // Sesuaikan dengan kebutuhan. Contoh: jika superadmin, wajib pilih BU.
            $data['business_unit_id'] = $request->business_unit_id ?? null;
        } else {
            $data['business_unit_id'] = $user->drmsProfile->business_unit_id;
        }

        Driver::create($data);

        return redirect()->route('drms.drivers.index')
            ->with('success', 'Driver berhasil ditambahkan.');
    }

    public function edit(Driver $driver)
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin()) {
            $this->checkBusinessUnit($driver);
        }
        return view('drms.drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin()) {
            $this->checkBusinessUnit($driver);
        }

        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'nullable|string|max:20',
            'status' => 'required|in:available,on_trip,off_duty',
        ]);

        $driver->update($data);

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

    // Ambil filter
    $date = $request->get('date', now()->format('Y-m-d'));
    $searchDriver = $request->get('search');
    $statusFilter = $request->get('status'); // pending, on_trip, completed, all

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

    if ($searchDriver) {
        $driverQuery->where('name', 'LIKE', '%' . $searchDriver . '%');
    }

    $drivers = $driverQuery->get();

    // Query requests
    $requestQuery = DriverRequest::with('driver', 'requester', 'requester.drmsProfile.businessUnit')
        ->where('usage_date', $date)
        ->whereIn('status', ['approved_admin', 'completed']);

    if ($statusFilter && $statusFilter != 'all') {
        if ($statusFilter == 'scheduled') {
            $requestQuery->where('status', 'approved_admin')
                ->whereTime('start_time', '>', now()->format('H:i:s'));
        } elseif ($statusFilter == 'on_trip') {
            $requestQuery->where('status', 'approved_admin')
                ->whereTime('start_time', '<=', now()->format('H:i:s'))
                ->whereTime('end_time', '>', now()->format('H:i:s'));
        } elseif ($statusFilter == 'completed') {
            $requestQuery->where('status', 'completed');
        }
    }

    // Filter berdasarkan driver yang sudah dipilih
    if ($searchDriver) {
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

    $requests = $requestQuery->orderBy('start_time')->get()->groupBy('driver_id');

    // Ambil daftar business unit untuk superadmin
    $businessUnits = [];
    if ($user->isDrmsSuperAdmin()) {
        $businessUnits = \App\Models\BisnisUnit::orderBy('nama_bisnis_unit')->get();
    }

    return view('drms.drivers.schedule', compact(
        'drivers', 'requests', 'date', 'searchDriver', 'statusFilter',
        'businessUnits', 'user'
    ));
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