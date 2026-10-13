<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Repair;
use App\Models\Drms\ServiceSchedule;
use App\Models\Drms\Vehicle;
use App\Services\DrmsServiceNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Driver mengajukan sendiri Servis Rutin / Perbaikan.
 * Pengajuan masuk dengan status 'open' -> menunggu approval admin GA
 * (Business Unit kendaraan) -> progress -> done (diselesaikan admin).
 */
class DriverServiceRequestController extends Controller
{
    private function driverOrAbort()
    {
        $driver = Auth::user()->driver;
        abort_unless($driver, 403, 'Data driver tidak ditemukan.');
        return $driver;
    }

    public function index()
    {
        $this->driverOrAbort();
        $uid = Auth::id();

        $services = ServiceSchedule::with('vehicle')
            ->where('created_by', $uid)
            ->latest()
            ->paginate(10, ['*'], 'svc_page');

        $repairs = Repair::with('vehicle')
            ->where('reported_by', $uid)
            ->latest()
            ->paginate(10, ['*'], 'rpr_page');

        return view('drms.drivers.service_requests.index', compact('services', 'repairs'));
    }

    public function create()
    {
        $driver = $this->driverOrAbort();
        abort_unless($driver->business_unit_id, 403, 'Driver belum memiliki Business Unit. Hubungi admin GA.');

        $vehicles = Vehicle::where('business_unit_id', $driver->business_unit_id)
            ->orderBy('plate_number')
            ->get();

        return view('drms.drivers.service_requests.create', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $driver = $this->driverOrAbort();
        if (!$driver->business_unit_id) {
            return back()->withErrors(['error' => 'Driver belum memiliki Business Unit. Hubungi admin GA.'])->withInput();
        }

        $type = $request->validate(['type' => 'required|in:service,repair'])['type'];

        // Kendaraan harus di Business Unit driver
        $vehicleRule = ['required', Rule::exists('drms_vehicles', 'id')->where('business_unit_id', $driver->business_unit_id)];

        if ($type === 'service') {
            $data = $request->validate([
                'vehicle_id'          => $vehicleRule,
                'service_date'        => 'required|date|after_or_equal:today',
                'odometer_at_service' => 'nullable|integer|min:0',
                'service_type'        => 'required|array|min:1',
                'service_type.*'      => 'string|max:50',
                'workshop_name'       => 'nullable|string|max:255',
                'notes'               => 'nullable|string|max:2000',
            ]);
            unset($data['type']);

            $item = ServiceSchedule::create($data + [
                'cost'       => 0,
                'status'     => 'open',
                'created_by' => Auth::id(),
            ]);
        } else {
            $data = $request->validate([
                'vehicle_id' => $vehicleRule,
                'complaint'  => 'required|string|max:2000',
                'notes'      => 'nullable|string|max:2000',
            ]);

            $item = Repair::create($data + [
                'report_date' => now()->toDateString(),
                'status'      => 'open',
                'reported_by' => Auth::id(),
                'created_by'  => Auth::id(),
            ]);
        }

        // Admin GA tetap dapat notif pengajuan (+ penerima tambahan dari tabel pengaturan)
        DrmsServiceNotifier::queue($type, $item, 'submitted', Auth::user());

        return redirect()->route('drms.driver.service.index')
            ->with('success', 'Pengajuan terkirim dan menunggu persetujuan admin GA.');
    }

    public function show(string $type, int $id)
    {
        $this->driverOrAbort();
        abort_unless(in_array($type, ['service', 'repair'], true), 404);

        if ($type === 'service') {
            $item = ServiceSchedule::with('vehicle', 'approver', 'completer')->findOrFail($id);
            abort_unless($item->created_by === Auth::id(), 403);
        } else {
            $item = Repair::with('vehicle', 'approver', 'completer')->findOrFail($id);
            abort_unless($item->reported_by === Auth::id(), 403);
        }

        return view('drms.drivers.service_requests.show', compact('type', 'item'));
    }
}
