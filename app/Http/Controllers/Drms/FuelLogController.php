<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\FuelLog;
use App\Models\Drms\Vehicle;
use App\Models\Drms\Driver;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FuelLogController extends Controller
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
        $query = FuelLog::with('vehicle', 'driver', 'user', 'verifier');
        if ($buId) {
            $query->whereHas('vehicle', fn($q) => $q->where('business_unit_id', $buId));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('filling_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('filling_date', '<=', $request->date_to);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function($q) use ($search) {
                $q->whereHas('vehicle', fn($sq) => $sq->where('plate_number', 'LIKE', $search))
                  ->orWhereHas('driver', fn($sq) => $sq->where('name', 'LIKE', $search));
            });
        }
        if ($request->filled('status')) {
            $query->where('is_verified', $request->status == 'verified' ? 1 : 0);
        }
        $logs = $query->latest()->paginate(20);
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))->get();
        return view('drms.fuel_logs.index', compact('logs', 'vehicles'));
    }

    public function create()
    {
        $buId = $this->getBusinessUnitId();
        // Semua driver (dari BU yang sama) bisa mengisi log BBM untuk kendaraan
        // yang berstatus "available" (tersedia). Tidak ada pembatasan per-driver.
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))
            ->where('status', 'available')
            ->orderBy('plate_number')
            ->get();
        $driver = Auth::user()->driver;
        return view('drms.fuel_logs.create', compact('vehicles', 'driver'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:drms_vehicles,id',
            'filling_date' => 'required|date',
            'odometer_start' => 'required|integer|min:0',
            'fuel_liters' => 'required|numeric|min:0.01',
            'fuel_total_price' => 'required|numeric|min:0',
            'receipt_file' => 'nullable|image|max:5120',
            'notes' => 'nullable|string',
        ]);

        $validated['fuel_price_per_liter'] = $validated['fuel_liters'] > 0
            ? $validated['fuel_total_price'] / $validated['fuel_liters']
            : 0;
        unset($validated['fuel_total_price']);

        $driver = Auth::user()->driver;
        $validated['driver_id'] = $driver ? $driver->id : null;
        $validated['user_id'] = Auth::id();
        $validated['is_verified'] = 0;

        if ($request->hasFile('receipt_file')) {
            $validated['receipt_file'] = ImageHelper::compressAndStore($request->file('receipt_file'), 'fuel_receipts');
        }

        FuelLog::create($validated);
        return redirect()->route('drms.fuel-logs.index')->with('success', 'Log BBM berhasil disimpan.');
    }

    public function show($id)
    {
        $log = FuelLog::with('vehicle', 'driver', 'user', 'verifier')->findOrFail($id);
        return view('drms.fuel_logs.show', compact('log'));
    }

    public function edit($id)
    {
        $log = FuelLog::findOrFail($id);
        $buId = $this->getBusinessUnitId();
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))
            ->where(function ($q) use ($log) {
                $q->where('status', 'available')
                  ->orWhere('id', $log->vehicle_id); // tetap tampilkan kendaraan yang sudah dipilih di log ini
            })
            ->orderBy('plate_number')
            ->get();
        $driver = Auth::user()->driver;
        return view('drms.fuel_logs.edit', compact('log', 'vehicles', 'driver'));
    }

    public function update(Request $request, $id)
    {
        $log = FuelLog::findOrFail($id);
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:drms_vehicles,id',
            'filling_date' => 'required|date',
            'odometer_start' => 'required|integer|min:0',
            'fuel_liters' => 'required|numeric|min:0.01',
            'fuel_total_price' => 'required|numeric|min:0',
            'receipt_file' => 'nullable|image|max:5120',
            'notes' => 'nullable|string',
        ]);

        $validated['fuel_price_per_liter'] = $validated['fuel_liters'] > 0
            ? $validated['fuel_total_price'] / $validated['fuel_liters']
            : 0;
        unset($validated['fuel_total_price']);

        $driver = Auth::user()->driver;
        $validated['driver_id'] = $driver ? $driver->id : null;

        if ($request->hasFile('receipt_file')) {
            if ($log->receipt_file) ImageHelper::deleteImage($log->receipt_file);
            $validated['receipt_file'] = ImageHelper::compressAndStore($request->file('receipt_file'), 'fuel_receipts');
        }
        $log->update($validated);
        return redirect()->route('drms.fuel-logs.index')->with('success', 'Log BBM diperbarui.');
    }

    public function verify(Request $request, $id)
    {
        $log = FuelLog::findOrFail($id);
        $log->is_verified = 1;
        $log->verified_by = Auth::id();
        $log->verified_at = now();
        $log->save();
        return redirect()->route('drms.fuel-logs.index')->with('success', 'Log BBM diverifikasi.');
    }

    public function destroy($id)
    {
        $log = FuelLog::findOrFail($id);
        if ($log->receipt_file) ImageHelper::deleteImage($log->receipt_file);
        $log->delete();
        return redirect()->route('drms.fuel-logs.index')->with('success', 'Log BBM dihapus.');
    }

    public function analytics(Request $request)
    {
        $buId = $this->getBusinessUnitId();

        $query = FuelLog::with('vehicle')
            ->where('is_verified', 1)
            ->when($buId, fn($q) => $q->whereHas('vehicle', fn($sq) => $sq->where('business_unit_id', $buId)));

        // Filter per kendaraan
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        // Filter periode (bulan ini / bulan lalu / custom range)
        if ($request->filled('date_from')) {
            $query->whereDate('filling_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('filling_date', '<=', $request->date_to);
        }

        $logs = $query->orderBy('vehicle_id')->orderBy('filling_date')->get();

        $grouped = $logs->groupBy('vehicle_id');
        $result = [];
        foreach ($grouped as $vehicleId => $items) {
            $vehicle = $items->first()->vehicle;
            if (!$vehicle) continue;

            // Total liter/kWh yang benar-benar dibeli pada periode ini (untuk info biaya & volume)
            $totalLiters = $items->sum('fuel_liters');
            $totalCost = $items->sum('total_cost');
            $count = $items->count();

            $totalDistance = 0;
            $litersForConsumption = 0; // liter yang "berpasangan" dengan jarak tempuh (tanpa liter pengisian pertama)
            $prevOdometer = null;
            foreach ($items as $item) {
                if ($prevOdometer !== null && $item->odometer_start > $prevOdometer) {
                    $totalDistance += ($item->odometer_start - $prevOdometer);
                    // liter pengisian ini dianggap "menutup" jarak sejak pengisian sebelumnya
                    $litersForConsumption += $item->fuel_liters;
                }
                $prevOdometer = $item->odometer_start;
            }

            // Konsumsi dihitung dari liter yang berpasangan dengan jarak saja,
            // supaya tidak bias oleh liter pengisian pertama yang tidak punya jarak pembanding.
            $avgConsumption = ($totalDistance > 0) ? round(($litersForConsumption / $totalDistance) * 100, 2) : null;

            $result[] = [
                'vehicle_id' => $vehicleId,
                'plate_number' => $vehicle->plate_number,
                'avg_consumption' => $avgConsumption,
                'total_liters' => $totalLiters,
                'total_cost' => $totalCost,
                'total_distance' => $totalDistance,
                'count' => $count,
                'fuel_type' => $vehicle->fuel_type,
            ];
        }
        usort($result, function ($a, $b) {
            if ($a['avg_consumption'] === null) return 1;
            if ($b['avg_consumption'] === null) return -1;
            return $a['avg_consumption'] <=> $b['avg_consumption'];
        });

        // Ringkasan total keseluruhan untuk periode/filter yang sedang aktif
        $summary = [
            'total_liters'   => $logs->sum('fuel_liters'),
            'total_cost'     => $logs->sum('total_cost'),
            'total_distance' => array_sum(array_column($result, 'total_distance')),
            'count'          => $logs->count(),
        ];

        // Daftar kendaraan untuk dropdown filter
        $vehicles = Vehicle::when($buId, fn($q) => $q->where('business_unit_id', $buId))
            ->orderBy('plate_number')
            ->get();

        return view('drms.fuel_logs.analytics', compact('result', 'summary', 'vehicles'));
    }
}