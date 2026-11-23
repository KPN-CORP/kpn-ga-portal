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

        // Filter jenis servis (kolom service_type sekarang array/JSON)
        if ($request->filled('service_type')) {
            $query->whereJsonContains('service_type', $request->service_type);
        }

        // Filter status pengajuan (open / progress / done / rejected)
        if ($request->filled('status') && in_array($request->status, ['open', 'progress', 'done', 'rejected'], true)) {
            $query->where('status', $request->status);
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
                $query->whereYear('service_date', $month);
            } elseif (preg_match('/^\d{4}-\d{2}$/', $month)) {
                [$year, $monthNum] = explode('-', $month);
                $query->whereYear('service_date', $year)->whereMonth('service_date', $monthNum);
            }
        }

        // Statistik dihitung dari SEMUA data hasil filter (bukan cuma 20 baris di halaman ini).
        $statRows = (clone $query)->setEagerLoads([])->get(['vehicle_id', 'cost', 'service_type']);
        $stats = [
            'total_cost'     => $statRows->sum('cost'),
            'oil_change'     => $statRows->filter(fn($s) => in_array('oil_change', $s->service_type ?? []))->count(),
            'general'        => $statRows->filter(fn($s) => in_array('general', $s->service_type ?? []))->count(),
            'unique_vehicles'=> $statRows->pluck('vehicle_id')->unique()->count(),
        ];

        $services = $query->latest()->paginate(20)->appends($request->query());

        // Data untuk dropdown filter
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))->get();

        // Pilihan dropdown periode: tahun & bulan dari data paling awal s.d. sekarang (terbaru di atas).
        $firstDate = ServiceSchedule::when($buId, fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $buId)))->min('service_date');
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

        return view('drms.service_schedules.index', compact('services', 'vehicles', 'month', 'stats', 'yearOptions', 'monthOptions'));
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
            'service_type' => 'required|array|min:1',
            'service_type.*' => 'string|max:50',
            'workshop_name' => 'nullable|string|max:255',
            'cost' => 'required|numeric|min:0',
            'invoice_file' => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120',
            'next_service_odometer' => 'nullable|integer|min:0',
            'next_service_date' => 'nullable|date|after:service_date',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('invoice_file')) {
            $validated['invoice_file'] = ImageHelper::compressOrStoreFile($request->file('invoice_file'), 'service_invoices');
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
            'service_type' => 'required|array|min:1',
            'service_type.*' => 'string|max:50',
            'workshop_name' => 'nullable|string|max:255',
            'cost' => 'required|numeric|min:0',
            'invoice_file' => 'nullable|mimes:jpg,jpeg,png,pdf|max:5120',
            'next_service_odometer' => 'nullable|integer|min:0',
            'next_service_date' => 'nullable|date|after:service_date',
            'notes' => 'nullable|string',
        ]);

        if ($request->hasFile('invoice_file')) {
            if ($service->invoice_file) ImageHelper::deleteImage($service->invoice_file);
            $validated['invoice_file'] = ImageHelper::compressOrStoreFile($request->file('invoice_file'), 'service_invoices');
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