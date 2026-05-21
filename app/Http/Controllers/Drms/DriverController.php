<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Driver;
use App\Models\Drms\DriverRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isDrmsSuperAdmin()) {
            // Superadmin bisa lihat semua driver
            $drivers = Driver::latest()->get();
        } else {
            $businessUnitId = $user->drmsProfile->business_unit_id ?? null;
            if (!$businessUnitId) {
                abort(403, 'Anda tidak memiliki unit bisnis.');
            }
            $drivers = Driver::where('business_unit_id', $businessUnitId)->latest()->get();
        }

        return view('drms.drivers.index', compact('drivers'));
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

        if ($user->isDrmsSuperAdmin()) {
            // Superadmin: bisa pilih BU atau lihat semua? Untuk schedule, kita batasi pilih BU via filter.
            // Jika ingin melihat semua driver dari semua BU, query tanpa where business_unit_id.
            $drivers = Driver::latest()->get();
        } else {
            $businessUnitId = $user->drmsProfile->business_unit_id ?? null;
            if (!$businessUnitId) {
                abort(403);
            }
            $drivers = Driver::where('business_unit_id', $businessUnitId)->get();
        }

        $date = $request->get('date', now()->format('Y-m-d'));

        $requests = DriverRequest::with('driver', 'requester')
            ->whereHas('driver', function ($q) use ($businessUnitId, $user) {
                if (!$user->isDrmsSuperAdmin() && $businessUnitId) {
                    $q->where('business_unit_id', $businessUnitId);
                }
                // Jika superadmin dan tidak ada filter BU, tampilkan semua request
            })
            ->where('usage_date', $date)
            ->whereIn('status', ['approved_admin', 'completed'])
            ->orderBy('start_time')
            ->get()
            ->groupBy('driver_id');

        return view('drms.drivers.schedule', compact('drivers', 'requests'));
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