<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    private function getUserBusinessUnitId()
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin()) {
            return null;
        }
        $profile = $user->drmsProfile;
        if (!$profile || !$profile->business_unit_id) {
            abort(403, 'Anda tidak memiliki unit bisnis.');
        }
        return $profile->business_unit_id;
    }

    /**
     * Display a listing of the vehicles with filters.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $this->getUserBusinessUnitId();

        $query = Vehicle::with('businessUnit');

        // Filter berdasarkan Business Unit (kecuali superadmin)
        if (!$user->isDrmsSuperAdmin()) {
            $query->where('business_unit_id', $businessUnitId);
        }

        // Filter: Business Unit (khusus superadmin)
        if ($user->isDrmsSuperAdmin() && $request->filled('business_unit_id')) {
            $query->where('business_unit_id', $request->business_unit_id);
        }

        // Filter: Pencarian (plat nomor atau tipe)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('plate_number', 'LIKE', $search)
                  ->orWhere('type', 'LIKE', $search);
            });
        }

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter: GPS Aktif
        if ($request->filled('gps_enabled')) {
            $query->where('gps_enabled', $request->gps_enabled == '1');
        }

        // Filter: Bahan Bakar
        if ($request->filled('fuel_type')) {
            $query->where('fuel_type', $request->fuel_type);
        }

        // Order by
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $vehicles = $query->paginate(20)->appends($request->query());

        // Ambil daftar business unit untuk dropdown (superadmin)
        $businessUnits = [];
        if ($user->isDrmsSuperAdmin()) {
            $businessUnits = \App\Models\BisnisUnit::orderBy('nama_bisnis_unit')->get();
        }

        // Ambil daftar fuel type unik untuk dropdown filter
        $fuelTypes = Vehicle::select('fuel_type')
            ->whereNotNull('fuel_type')
            ->distinct()
            ->pluck('fuel_type')
            ->toArray();

        return view('drms.vehicles.index', compact(
            'vehicles',
            'businessUnits',
            'fuelTypes'
        ));
    }

    /**
     * Show the form for creating a new vehicle.
     */
    public function create()
    {
        $this->getUserBusinessUnitId(); // validasi akses
        return view('drms.vehicles.create');
    }

    /**
     * Store a newly created vehicle in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'type'         => 'required|string|max:255',
            'plate_number' => 'required|string|max:20|unique:drms_vehicles',
            'capacity'     => 'nullable|integer|min:1',
            'status'       => 'required|in:available,in_use,maintenance',
            'gps_enabled'  => 'sometimes|boolean',
            'fuel_type'    => 'nullable|in:Bensin,Solar,Listrik,Hybrid,Lainnya',
        ]);

        // Konversi checkbox
        $data['gps_enabled'] = $request->has('gps_enabled');

        if ($user->isDrmsSuperAdmin()) {
            $data['business_unit_id'] = $request->business_unit_id ?? null;
        } else {
            $data['business_unit_id'] = $this->getUserBusinessUnitId();
        }

        Vehicle::create($data);

        return redirect()->route('drms.vehicles.index')
            ->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified vehicle.
     */
    public function edit(Vehicle $vehicle)
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin()) {
            $businessUnitId = $this->getUserBusinessUnitId();
            if ($vehicle->business_unit_id !== $businessUnitId) {
                abort(403, 'Anda tidak memiliki akses ke kendaraan ini.');
            }
        }
        return view('drms.vehicles.edit', compact('vehicle'));
    }

    /**
     * Update the specified vehicle in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin()) {
            $businessUnitId = $this->getUserBusinessUnitId();
            if ($vehicle->business_unit_id !== $businessUnitId) {
                abort(403, 'Anda tidak memiliki akses ke kendaraan ini.');
            }
        }

        $data = $request->validate([
            'type'         => 'required|string|max:255',
            'plate_number' => 'required|string|max:20|unique:drms_vehicles,plate_number,' . $vehicle->id,
            'capacity'     => 'nullable|integer|min:1',
            'status'       => 'required|in:available,in_use,maintenance',
            'gps_enabled'  => 'sometimes|boolean',
            'fuel_type'    => 'nullable|in:Bensin,Solar,Listrik,Hybrid,Lainnya',
        ]);

        $data['gps_enabled'] = $request->has('gps_enabled');
        $vehicle->update($data);

        return redirect()->route('drms.vehicles.index')
            ->with('success', 'Kendaraan berhasil diperbarui.');
    }

    /**
     * Remove the specified vehicle from storage.
     */
    public function destroy(Vehicle $vehicle)
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin()) {
            $businessUnitId = $this->getUserBusinessUnitId();
            if ($vehicle->business_unit_id !== $businessUnitId) {
                abort(403, 'Anda tidak memiliki akses ke kendaraan ini.');
            }
        }
        $vehicle->delete();
        return redirect()->route('drms.vehicles.index')
            ->with('success', 'Kendaraan dihapus.');
    }
}