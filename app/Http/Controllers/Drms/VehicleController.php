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

    public function index()
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin()) {
            $vehicles = Vehicle::latest()->get();
        } else {
            $businessUnitId = $this->getUserBusinessUnitId();
            $vehicles = Vehicle::where('business_unit_id', $businessUnitId)->latest()->get();
        }
        return view('drms.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        $this->getUserBusinessUnitId(); // validasi akses
        return view('drms.vehicles.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'type'         => 'required|string|max:255',
            'plate_number' => 'required|string|max:20|unique:drms_vehicles',
            'capacity'     => 'nullable|integer|min:1',
            'status'       => 'required|in:available,in_use,maintenance',
            'gps_enabled'  => 'sometimes|boolean',
        ]);

        // Konversi checkbox: jika tidak ada, bernilai false
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
        ]);

        $data['gps_enabled'] = $request->has('gps_enabled');
        $vehicle->update($data);

        return redirect()->route('drms.vehicles.index')
            ->with('success', 'Kendaraan berhasil diperbarui.');
    }

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