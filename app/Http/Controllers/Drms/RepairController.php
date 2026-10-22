<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Repair;
use App\Models\Drms\Vehicle;
use App\Helpers\ImageHelper;
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

        // Filter Plat + Merek (gabungan, semua kata harus cocok di plat ATAU merek kendaraan)
        if ($request->filled('plate_brand')) {
            $term = $request->plate_brand;
            $query->whereHas('vehicle', fn($q) => $q->searchPlateBrand($term));
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

        // Filter Periode (DEFAULT: tahun berjalan), kecuali user sudah memakai filter tanggal manual.
        // Nilai month: 'all' = semua | 'YYYY' = 1 tahun | 'YYYY-MM' = 1 bulan.
        $month = $request->get('month', now()->format('Y'));
        if (!$request->filled('date_from') && !$request->filled('date_to')) {
            if (preg_match('/^\d{4}$/', $month)) {
                $query->whereYear('report_date', $month);
            } elseif (preg_match('/^\d{4}-\d{2}$/', $month)) {
                [$year, $monthNum] = explode('-', $month);
                $query->whereYear('report_date', $year)->whereMonth('report_date', $monthNum);
            }
        }

        // Statistik dihitung dari SEMUA data hasil filter (bukan cuma 20 baris di halaman ini).
        $stats = [
            'open'       => (clone $query)->where('status', 'open')->count(),
            'progress'   => (clone $query)->where('status', 'progress')->count(),
            'done'       => (clone $query)->where('status', 'done')->count(),
            'total_cost' => (clone $query)->sum('total_cost'),
        ];

        $repairs = $query->latest()->paginate(20)->appends($request->query());

        // Data untuk dropdown filter
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))->get();

        // Pilihan dropdown periode: tahun & bulan dari data paling awal s.d. sekarang (terbaru di atas).
        $firstDate = Repair::when($buId, fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $buId)))->min('report_date');
        $first = $firstDate ? \Carbon\Carbon::parse($firstDate)->startOfMonth() : now()->startOfMonth();
        // Periode yang sedang dipilih (mis. dari link dashboard) tetap masuk dropdown walau lebih lama dari data.
        if (preg_match('/^(\d{4})(?:-(0[1-9]|1[0-2]))?$/', $month, $mm)) {
            $selected = \Carbon\Carbon::create((int) $mm[1], isset($mm[2]) ? (int) $mm[2] : 1, 1)->startOfMonth();
            if ($selected->lt($first)) $first = $selected;
        }
        $yearOptions = range((int) now()->year, (int) $first->year);
        $monthOptions = [];
        for ($d = now()->startOfMonth(); $d->gte($first); $d = $d->copy()->subMonth()) {
            $monthOptions[] = $d->format('Y-m');
        }

        return view('drms.repairs.index', compact('repairs', 'vehicles', 'month', 'stats', 'yearOptions', 'monthOptions'));
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
            'invoice_file' => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string',
        ]);
        $validated['reported_by'] = Auth::id();
        $validated['created_by'] = Auth::id();

        if ($request->hasFile('invoice_file')) {
            $validated['invoice_file'] = ImageHelper::compressOrStoreFile($request->file('invoice_file'), 'repair_invoices');
        }

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
            'status' => 'required|in:open,progress,done,rejected',
            'invoice_file' => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string',
        ]);
        if ($validated['status'] === 'done' && $repair->status !== 'done') {
            $validated['completed_at'] = now();
        }

        if ($request->hasFile('invoice_file')) {
            if ($repair->invoice_file) ImageHelper::deleteImage($repair->invoice_file);
            $validated['invoice_file'] = ImageHelper::compressOrStoreFile($request->file('invoice_file'), 'repair_invoices');
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
        $repair = Repair::findOrFail($id);
        if ($repair->invoice_file) ImageHelper::deleteImage($repair->invoice_file);
        $repair->delete();
        return redirect()->route('drms.repairs.index')
            ->with('success', 'Perbaikan dihapus.');
    }
}