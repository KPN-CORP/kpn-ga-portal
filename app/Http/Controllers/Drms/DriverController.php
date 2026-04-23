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
        $businessUnitId = Auth::user()->drmsProfile->business_unit_id ?? null;
        if (!$businessUnitId) {
            abort(403, 'Anda tidak memiliki unit bisnis.');
        }

        $drivers = Driver::where('business_unit_id', $businessUnitId)
            ->latest()
            ->get();
        return view('drms.drivers.index', compact('drivers'));
    }

    public function create()
    {
        return view('drms.drivers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string|max:255',
            'phone'  => 'nullable|string|max:20',
            'status' => 'required|in:available,on_trip,off_duty',
        ]);

        $data['business_unit_id'] = Auth::user()->drmsProfile->business_unit_id;

        Driver::create($data);

        return redirect()->route('drms.drivers.index')
            ->with('success', 'Driver berhasil ditambahkan.');
    }

    public function edit(Driver $driver)
    {
        $this->authorize('update', $driver); // Asumsikan ada Policy
        return view('drms.drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $this->authorize('update', $driver);

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
        $this->authorize('delete', $driver);
        $driver->delete();
        return redirect()->route('drms.drivers.index')
            ->with('success', 'Driver dihapus.');
    }

    public function schedule(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $user->drmsProfile->business_unit_id ?? null;
        if (!$businessUnitId) {
            abort(403);
        }

        $date = $request->get('date', now()->format('Y-m-d'));

        $drivers = Driver::where('business_unit_id', $businessUnitId)->get();

        $requests = DriverRequest::with('driver', 'requester')
            ->whereHas('driver', function ($q) use ($businessUnitId) {
                $q->where('business_unit_id', $businessUnitId);
            })
            ->where('usage_date', $date)
            ->whereIn('status', ['approved_admin', 'completed'])
            ->orderBy('start_time')
            ->get()
            ->groupBy('driver_id');

        return view('drms.drivers.schedule', compact('drivers', 'requests'));
    }
}