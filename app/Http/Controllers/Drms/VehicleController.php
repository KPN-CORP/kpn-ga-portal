<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    /**
     * Ambil business_unit_id user dari profil DRMS, dengan fallback aman.
     */
    private function getUserBusinessUnitId()
    {
        $profile = Auth::user()->drmsProfile;
        if (!$profile || !$profile->business_unit_id) {
            abort(403, 'Anda tidak memiliki unit bisnis.');
        }
        return $profile->business_unit_id;
    }

    public function index()
    {
        $businessUnitId = $this->getUserBusinessUnitId();
        $vehicles = Vehicle::where('business_unit_id', $businessUnitId)
            ->latest()
            ->get();
        return view('drms.vehicles.index', compact('vehicles'));
    }

    public function create()
    {
        $this->getUserBusinessUnitId(); // memastikan user valid
        return view('drms.vehicles.create');
    }

    public function store(Request $request)
    {
        $businessUnitId = $this->getUserBusinessUnitId();

        $data = $request->validate([
            'type'         => 'required|string|max:255',
            'plate_number' => 'required|string|max:20|unique:drms_vehicles',
            'capacity'     => 'nullable|integer|min:1',
            'status'       => 'required|in:available,in_use,maintenance',
        ]);

        $data['business_unit_id'] = $businessUnitId;

        Vehicle::create($data);

        return redirect()->route('drms.vehicles.index')
            ->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    public function edit(Vehicle $vehicle)
    {
        $businessUnitId = $this->getUserBusinessUnitId();
        if ($vehicle->business_unit_id !== $businessUnitId) {
            abort(403, 'Anda tidak memiliki akses ke kendaraan ini.');
        }
        return view('drms.vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $businessUnitId = $this->getUserBusinessUnitId();
        if ($vehicle->business_unit_id !== $businessUnitId) {
            abort(403, 'Anda tidak memiliki akses ke kendaraan ini.');
        }

        $data = $request->validate([
            'type'         => 'required|string|max:255',
            'plate_number' => 'required|string|max:20|unique:drms_vehicles,plate_number,' . $vehicle->id,
            'capacity'     => 'nullable|integer|min:1',
            'status'       => 'required|in:available,in_use,maintenance',
        ]);

        $vehicle->update($data);

        return redirect()->route('drms.vehicles.index')
            ->with('success', 'Kendaraan berhasil diperbarui.');
    }

    public function destroy(Vehicle $vehicle)
    {
        $businessUnitId = $this->getUserBusinessUnitId();
        if ($vehicle->business_unit_id !== $businessUnitId) {
            abort(403, 'Anda tidak memiliki akses ke kendaraan ini.');
        }
        $vehicle->delete();
        return redirect()->route('drms.vehicles.index')
            ->with('success', 'Kendaraan dihapus.');
    }
}