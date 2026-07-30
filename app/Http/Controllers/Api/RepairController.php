<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RepairResource;
use App\Models\Drms\Repair;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API Perbaikan — VIEW ONLY, untuk integrasi sistem eksternal.
 * Kendaraan diidentifikasi pakai plat nomor (bukan vehicle_id).
 */
class RepairController extends Controller
{
    /**
     * GET /api/v1/repairs
     * Query params opsional:
     * - plate_number   : filter plat nomor (boleh sebagian)
     * - updated_since   : ISO date/datetime, cuma tampilkan yang diupdate sejak tanggal itu
     * - status          : open / progress / done
     * - date_from / date_to : filter report_date
     * - per_page        : default 20, maksimal 100
     */
    public function index(Request $request)
    {
        $query = Repair::with('vehicle');

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
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('report_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('report_date', '<=', $request->date_to);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $repairs = $query->orderByDesc('updated_at')->paginate($perPage)->appends($request->query());

        return RepairResource::collection($repairs)->response();
    }

    /**
     * GET /api/v1/repairs/{id}
     */
    public function show($id)
    {
        $repair = Repair::with('vehicle')->findOrFail($id);
        return (new RepairResource($repair))->response();
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
