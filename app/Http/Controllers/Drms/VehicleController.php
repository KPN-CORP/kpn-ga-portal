<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleController extends Controller
{
    /**
     * Menampilkan daftar kendaraan (hanya unit yang sama dengan admin).
     */
    public function index()
    {
        $vehicles = Vehicle::where('business_unit_id', Auth::user()->business_unit_id)
            ->latest()
            ->get();
        return view('drms.vehicles.index', compact('vehicles'));
    }

    /**
     * Form tambah kendaraan.
     */
    public function create()
    {
        return view('drms.vehicles.create');
    }

    /**
     * Simpan kendaraan baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type'          => 'required|string|max:255',
            'plate_number'  => 'required|string|max:20|unique:drms_vehicles',
            'capacity'      => 'nullable|integer|min:1',
            'status'        => 'required|in:available,in_use,maintenance',
        ]);

        $data['business_unit_id'] = Auth::user()->business_unit_id;

        Vehicle::create($data);

        return redirect()->route('drms.vehicles.index')
            ->with('success', 'Kendaraan berhasil ditambahkan.');
    }

    /**
     * Form edit kendaraan.
     */
    public function edit(Vehicle $vehicle)
    {
        // Pastikan kendaraan hanya bisa diedit oleh admin di unit yang sama
        if ($vehicle->business_unit_id !== Auth::user()->business_unit_id) {
            abort(403);
        }
        return view('drms.vehicles.edit', compact('vehicle'));
    }

    /**
     * Update kendaraan.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        if ($vehicle->business_unit_id !== Auth::user()->business_unit_id) {
            abort(403);
        }

        $data = $request->validate([
            'type'          => 'required|string|max:255',
            'plate_number'  => 'required|string|max:20|unique:drms_vehicles,plate_number,' . $vehicle->id,
            'capacity'      => 'nullable|integer|min:1',
            'status'        => 'required|in:available,in_use,maintenance',
        ]);

        $vehicle->update($data);

        return redirect()->route('drms.vehicles.index')
            ->with('success', 'Kendaraan berhasil diperbarui.');
    }

    /**
     * Hapus kendaraan.
     */
    public function destroy(Vehicle $vehicle)
    {
        if ($vehicle->business_unit_id !== Auth::user()->business_unit_id) {
            abort(403);
        }
        $vehicle->delete();
        return redirect()->route('drms.vehicles.index')
            ->with('success', 'Kendaraan dihapus.');
    }
}