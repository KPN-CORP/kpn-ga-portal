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
        $drivers = Driver::where('business_unit_id', Auth::user()->business_unit_id)
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
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:available,on_trip,off_duty',
        ]);

        $data['business_unit_id'] = Auth::user()->business_unit_id;

        Driver::create($data);

        return redirect()->route('drms.drivers.index')
            ->with('success', 'Driver berhasil ditambahkan.');
    }

    public function edit(Driver $driver)
    {
        if ($driver->business_unit_id !== Auth::user()->business_unit_id) {
            abort(403);
        }
        return view('drms.drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        if ($driver->business_unit_id !== Auth::user()->business_unit_id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:available,on_trip,off_duty',
        ]);

        $driver->update($data);

        return redirect()->route('drms.drivers.index')
            ->with('success', 'Driver berhasil diperbarui.');
    }

    public function destroy(Driver $driver)
    {
        if ($driver->business_unit_id !== Auth::user()->business_unit_id) {
            abort(403);
        }
        $driver->delete();
        return redirect()->route('drms.drivers.index')
            ->with('success', 'Driver dihapus.');
    }

    public function schedule(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $user->business_unit_id;
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
    public function scopeOverlapping($query, $driverId, $date, $start, $end, $excludeId = null)
    {
        $query->where('driver_id', $driverId)
            ->where('usage_date', $date)
            ->whereIn('status', ['approved_admin', 'pending_l1', 'approved_l1'])
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end])
                    ->orWhereBetween('end_time', [$start, $end])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_time', '<=', $start)
                        ->where('end_time', '>=', $end);
                    });
            });
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query;
    }
}