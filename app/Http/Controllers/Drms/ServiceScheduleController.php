<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\ServiceSchedule;
use App\Models\Drms\Vehicle;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceScheduleController extends Controller
{
    private function getBusinessUnitId()
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin()) return null;
        return $user->drmsProfile->business_unit_id ?? abort(403);
    }

    public function index(Request $request)
    {
        $buId = $this->getBusinessUnitId();

        $query = ServiceSchedule::with('vehicle');

        // Filter Business Unit
        if ($buId) {
            $query->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
        }

        // Filter berdasarkan pencarian kendaraan
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->whereHas('vehicle', function ($q) use ($search) {
                $q->where('plate_number', 'LIKE', $search)
                  ->orWhere('type', 'LIKE', $search);
            });
        }

        // Filter tanggal servis (mulai)
        if ($request->filled('date_from')) {
            $query->whereDate('service_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('service_date', '<=', $request->date_to);
        }

        // Filter jenis servis
        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        // Filter kendaraan
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $services = $query->latest()->paginate(20)->appends($request->query());

        // Data untuk dropdown filter
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))->get();

        return view('drms.service_schedules.index', compact('services', 'vehicles'));
    }

    public function create()
    {
        $buId = $this->getBusinessUnitId();
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))->get();
        return view('drms.service_schedules.create', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:drms_vehicles,id',
            'service_date' => 'required|date',
            'odometer_at_service' => 'nullable|integer|min:0',
            'service_type' => 'required|in:oil_change,filter_change,tune_up,spooring,balancing,general',
            'workshop_name' => 'nullable|string|max:255',
            'cost' => 'required|numeric|min:0',
            'invoice_file' => 'nullable|image|max:5120',
            'next_service_odometer' => 'nullable|integer|min:0',
            'next_service_date' => 'nullable|date|after:service_date',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('invoice_file')) {
            $validated['invoice_file'] = ImageHelper::compressAndStore($request->file('invoice_file'), 'service_invoices');
        }
        $validated['created_by'] = Auth::id();

        ServiceSchedule::create($validated);
        return redirect()->route('drms.service-schedules.index')
            ->with('success', 'Servis berhasil ditambahkan.');
    }

    public function show($id)
    {
        $service = ServiceSchedule::with('vehicle', 'creator')->findOrFail($id);
        return view('drms.service_schedules.show', compact('service'));
    }

    public function edit($id)
    {
        $service = ServiceSchedule::findOrFail($id);
        $buId = $this->getBusinessUnitId();
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))->get();
        return view('drms.service_schedules.edit', compact('service', 'vehicles'));
    }

    public function update(Request $request, $id)
    {
        $service = ServiceSchedule::findOrFail($id);
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:drms_vehicles,id',
            'service_date' => 'required|date',
            'odometer_at_service' => 'nullable|integer|min:0',
            'service_type' => 'required|in:oil_change,filter_change,tune_up,spooring,balancing,general',
            'workshop_name' => 'nullable|string|max:255',
            'cost' => 'required|numeric|min:0',
            'invoice_file' => 'nullable|image|max:5120',
            'next_service_odometer' => 'nullable|integer|min:0',
            'next_service_date' => 'nullable|date|after:service_date',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('invoice_file')) {
            if ($service->invoice_file) ImageHelper::deleteImage($service->invoice_file);
            $validated['invoice_file'] = ImageHelper::compressAndStore($request->file('invoice_file'), 'service_invoices');
        }
        $service->update($validated);
        return redirect()->route('drms.service-schedules.index')
            ->with('success', 'Servis berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $service = ServiceSchedule::findOrFail($id);
        if ($service->invoice_file) ImageHelper::deleteImage($service->invoice_file);
        $service->delete();
        return redirect()->route('drms.service-schedules.index')
            ->with('success', 'Servis dihapus.');
    }
}