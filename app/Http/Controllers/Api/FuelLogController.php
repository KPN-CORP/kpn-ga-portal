<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\FuelLogResource;
use App\Models\Drms\FuelLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API Pengisian BBM — VIEW ONLY, untuk integrasi sistem eksternal.
 * Kendaraan diidentifikasi pakai plat nomor (bukan vehicle_id).
 */
class FuelLogController extends Controller
{
    /**
     * GET /api/v1/fuel-logs
     * Query params opsional:
     * - plate_number   : filter plat nomor (boleh sebagian, mis. "1234")
     * - updated_since   : ISO date/datetime, cuma tampilkan yang diupdate sejak tanggal itu
     * - is_verified     : 1 / 0, filter status verifikasi
     * - date_from / date_to : filter filling_date
     * - per_page        : default 20, maksimal 100
     */
    public function index(Request $request)
    {
        $query = FuelLog::with('vehicle');

        $buId = $this->getBusinessUnitId();
        if ($buId) {
            $query->whereHas('vehicle', fn ($q) => $q->where('business_unit_id', $buId));
        }

        if ($request->filled('plate_number')) {
            $plate = str_replace(' ', '', $request->plate_number);
            $query->whereHas('vehicle', function ($q) use ($plate) {
                $q->whereRaw("REPLACE(plate_number, ' ', '') LIKE ?", ["%{$plate}%"]);
            });
        }
        if ($request->filled('updated_since')) {
            $query->where('updated_at', '>=', $request->updated_since);
        }
        if ($request->filled('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('filling_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('filling_date', '<=', $request->date_to);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $fuelLogs = $query->orderByDesc('updated_at')->paginate($perPage)->appends($request->query());

        return FuelLogResource::collection($fuelLogs)->response();
    }

    /**
     * GET /api/v1/fuel-logs/{id}
     */
    public function show($id)
    {
        $fuelLog = FuelLog::with('vehicle')->findOrFail($id);
        return (new FuelLogResource($fuelLog))->response();
    }

    private function getBusinessUnitId()
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin()) {
            return null;
        }
        return $user->drmsProfile->business_unit_id ?? null;
    }
}
