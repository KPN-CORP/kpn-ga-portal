<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Repair;
use App\Models\Drms\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RepairController extends Controller
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

        $query = Repair::with('vehicle', 'reporter');

        // Filter Business Unit
        if ($buId) {
            $query->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
        }

        // Filter pencarian (kendaraan atau keluhan)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->whereHas('vehicle', function ($q2) use ($search) {
                    $q2->where('plate_number', 'LIKE', $search)
                       ->orWhere('type', 'LIKE', $search);
                })->orWhere('complaint', 'LIKE', $search);
            });
        }

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter tanggal
        if ($request->filled('date_from')) {
            $query->whereDate('report_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('report_date', '<=', $request->date_to);
        }

        // Filter kendaraan
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        $repairs = $query->latest()->paginate(20)->appends($request->query());

        // Data untuk dropdown filter
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))->get();

        return view('drms.repairs.index', compact('repairs', 'vehicles'));
    }

    public function create()
    {
        $buId = $this->getBusinessUnitId();
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))->get();
        return view('drms.repairs.create', compact('vehicles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:drms_vehicles,id',
            'report_date' => 'required|date',
            'complaint' => 'required|string',
            'diagnosis' => 'nullable|string',
            'parts_replaced' => 'nullable|string',
            'labor_cost' => 'nullable|numeric|min:0',
            'parts_cost' => 'nullable|numeric|min:0',
            'status' => 'required|in:open,progress,done',
            'notes' => 'nullable|string',
        ]);
        $validated['reported_by'] = Auth::id();
        $validated['created_by'] = Auth::id();
        Repair::create($validated);
        return redirect()->route('drms.repairs.index')
            ->with('success', 'Laporan perbaikan berhasil dibuat.');
    }

    public function show($id)
    {
        $repair = Repair::with('vehicle', 'reporter', 'creator')->findOrFail($id);
        return view('drms.repairs.show', compact('repair'));
    }

    public function edit($id)
    {
        $repair = Repair::findOrFail($id);
        $buId = $this->getBusinessUnitId();
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))->get();
        return view('drms.repairs.edit', compact('repair', 'vehicles'));
    }

    public function update(Request $request, $id)
    {
        $repair = Repair::findOrFail($id);
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:drms_vehicles,id',
            'report_date' => 'required|date',
            'complaint' => 'required|string',
            'diagnosis' => 'nullable|string',
            'parts_replaced' => 'nullable|string',
            'labor_cost' => 'nullable|numeric|min:0',
            'parts_cost' => 'nullable|numeric|min:0',
            'status' => 'required|in:open,progress,done',
            'notes' => 'nullable|string',
        ]);
        if ($validated['status'] === 'done' && $repair->status !== 'done') {
            $validated['completed_at'] = now();
        }
        $repair->update($validated);
        return redirect()->route('drms.repairs.index')
            ->with('success', 'Perbaikan diperbarui.');
    }

    public function updateStatus(Request $request, $id)
    {
        $repair = Repair::findOrFail($id);
        $request->validate(['status' => 'required|in:open,progress,done']);
        $data = ['status' => $request->status];
        if ($request->status === 'done') $data['completed_at'] = now();
        $repair->update($data);
        return redirect()->route('drms.repairs.index')
            ->with('success', 'Status diperbarui.');
    }

    public function destroy($id)
    {
        Repair::findOrFail($id)->delete();
        return redirect()->route('drms.repairs.index')
            ->with('success', 'Perbaikan dihapus.');
    }
}