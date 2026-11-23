<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RepairResource;
use App\Models\Drms\Repair;
use App\Support\OwnerLookupCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API Perbaikan — VIEW ONLY, untuk integrasi sistem eksternal.
 * Kendaraan diidentifikasi pakai plat nomor (bukan vehicle_id).
 */
class RepairController extends Controller
{
    /**
     * Mapping company_group (AMS) -> business_unit_id (DRMS), lihat
     * Manual Book v3.0 bagian 4. Dipakai supaya param business_unit_id
     * juga menerima kode grup (mis. "CORP"), bukan cuma angka.
     */
    private const BU_GROUP_MAP = [
        'CORP' => 1,
        'CMT'  => 2,
        'PTY'  => 3,
        'PLT'  => 4,
        'DWS'  => 5,
    ];

    /**
     * GET /api/v1/repairs
     * Query params opsional:
     * - plate_number   : filter plat nomor (boleh sebagian)
     * - business_unit_id : filter 1 business unit — angka (1-5) atau kode
     *                      grup AMS (CORP/CMT/PTY/PLT/DWS). Token
     *                      non-superadmin cuma boleh minta BU miliknya
     *                      sendiri (kalau dikirim BU lain -> 403).
     * - updated_since   : ISO date/datetime, cuma tampilkan yang diupdate sejak tanggal itu
     * - status          : open / progress / done
     * - date_from / date_to : filter report_date
     * - per_page        : default 20, maksimal 100
     */
    public function index(Request $request)
    {
        $query = Repair::with('vehicle');

        $buId = $this->resolveBusinessUnitId($request);
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

        // BARU (v3.0): preload owner untuk semua plat di halaman ini sekaligus
        // (1 query batch), supaya RepairResource tinggal baca dari cache.
        OwnerLookupCache::preload(
            $repairs->getCollection()->pluck('vehicle.plate_number')->all()
        );

        return RepairResource::collection($repairs)->response();
    }

    /**
     * GET /api/v1/repairs/{id}
     */
    public function show($id)
    {
        $repair = Repair::with('vehicle')->findOrFail($id);

        // BARU (v3.0)
        OwnerLookupCache::preload([$repair->vehicle->plate_number ?? null]);

        return (new RepairResource($repair))->response();
    }

    /**
     * BARU: sebelumnya method ini (getBusinessUnitId) SELALU mengambil BU
     * dari akun token sendiri dan mengabaikan total query param apa pun.
     * Sekarang: kalau param business_unit_id dikirim, dipakai (setelah
     * divalidasi/di-resolve dari kode grup kalau perlu). Token
     * non-superadmin tetap dibatasi ke BU miliknya sendiri saja.
     */
    private function resolveBusinessUnitId(Request $request)
    {
        $user = Auth::user();
        $ownBuId = $user->isDrmsSuperAdmin() ? null : ($user->drmsProfile->business_unit_id ?? null);

        if (! $request->filled('business_unit_id')) {
            return $ownBuId;
        }

        $raw = $request->business_unit_id;
        $requested = is_numeric($raw)
            ? (int) $raw
            : (self::BU_GROUP_MAP[strtoupper(trim($raw))] ?? null);

        abort_if($requested === null, 422, 'business_unit_id tidak dikenali. Gunakan angka 1-5 atau kode: ' . implode('/', array_keys(self::BU_GROUP_MAP)));

        if (! $user->isDrmsSuperAdmin() && $requested !== $ownBuId) {
            abort(403, 'business_unit_id di luar akses token ini.');
        }

        return $requested;
    }
}