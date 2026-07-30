<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceScheduleResource;
use App\Models\Drms\ServiceSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API Servis Rutin — VIEW ONLY, untuk integrasi sistem eksternal.
 * Kendaraan diidentifikasi pakai plat nomor (bukan vehicle_id).
 */
class ServiceScheduleController extends Controller
{
    /**
     * GET /api/v1/service-schedules
     * Query params opsional:
     * - plate_number   : filter plat nomor (boleh sebagian, mis. "B 1234" cukup ketik "1234")
     * - updated_since   : ISO date/datetime, cuma tampilkan yang diupdate sejak tanggal itu
     *                      (dipakai buat sinkronisasi berkala — cek data apa saja yang berubah)
     * - date_from / date_to : filter service_date
     * - per_page        : default 20, maksimal 100
     */
    public function index(Request $request)
    {
        $query = ServiceSchedule::with('vehicle');

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
        if ($request->filled('date_from')) {
            $query->whereDate('service_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('service_date', '<=', $request->date_to);
        }

        $perPage = min((int) $request->get('per_page', 20), 100);
        $services = $query->orderByDesc('updated_at')->paginate($perPage)->appends($request->query());

        return ServiceScheduleResource::collection($services)->response();
    }

    /**
     * GET /api/v1/service-schedules/{id}
     */
    public function show($id)
    {
        $service = ServiceSchedule::with('vehicle')->findOrFail($id);
        return (new ServiceScheduleResource($service))->response();
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
