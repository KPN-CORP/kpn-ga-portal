<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\AreaKerja;
use App\Models\HSRM\HsrmCertificateQuota;
use App\Models\HSRM\HsrmEquipmentQuota;
use App\Models\HSRM\HsrmCertificateType;
use App\Models\HSRM\HsrmEquipmentType;
use App\Models\HSRM\HsrmCertificate;
use App\Models\HSRM\HsrmEquipment;
use App\Exports\HsrmQuotaExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class HsrmQuotaController extends Controller
{
    public function __construct()
    {
        // Admin & PIC boleh membuka halaman kuota. PIC hanya melihat
        // kuota area miliknya sendiri dalam mode read-only (lihat index()).
        $this->middleware(function ($request, $next) {
            if (!in_array(session('hsrm_role'), ['admin', 'pic'])) {
                abort(403, 'Unauthorized.');
            }
            return $next($request);
        });

        // Update & export tetap khusus admin.
        $this->middleware(function ($request, $next) {
            if (session('hsrm_role') !== 'admin') {
                abort(403, 'Only admin can manage quotas.');
            }
            return $next($request);
        })->only(['update', 'export']);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';

        if ($isAdmin) {
            $areas = AreaKerja::orderBy('nama_area')->get();
            $selectedArea = $request->get('area_id') ? AreaKerja::find($request->area_id) : null;
        } else {
            // PIC hanya boleh melihat area yang menjadi tanggung jawabnya.
            $areas = $user->hsrmAreas()->orderBy('nama_area')->get();
            $allowedAreaIds = $areas->pluck('id_area_kerja')->toArray();

            $requestedAreaId = $request->get('area_id');
            if ($requestedAreaId && in_array($requestedAreaId, $allowedAreaIds)) {
                $selectedArea = AreaKerja::find($requestedAreaId);
            } elseif ($areas->count() === 1) {
                // Jika PIC hanya punya satu area, langsung tampilkan otomatis.
                $selectedArea = $areas->first();
            } else {
                $selectedArea = null;
            }
        }

        $certificateData = [];
        $equipmentData = [];

        if ($selectedArea) {
            // Certificate
            $types = HsrmCertificateType::orderBy('name')->get();
            foreach ($types as $type) {
                $quota = HsrmCertificateQuota::where('area_id', $selectedArea->id_area_kerja)
                            ->where('certificate_type_id', $type->id)
                            ->first();

                $active = HsrmCertificate::where('area_id', $selectedArea->id_area_kerja)
                            ->where('certificate_type_id', $type->id)
                            ->where('status_verif', 'verified')
                            ->notExpired()
                            ->count();

                $expired = HsrmCertificate::where('area_id', $selectedArea->id_area_kerja)
                            ->where('certificate_type_id', $type->id)
                            ->whereNotNull('expired_date')
                            ->where('expired_date', '<=', now())
                            ->count();

                $certificateData[] = (object) [
                    'type' => $type,
                    'quota' => $quota ? $quota->quota : 0,
                    'budget' => $quota ? $quota->budget : null,
                    'active' => $active,
                    'expired' => $expired,
                    'quota_id' => $quota ? $quota->id : null,
                    'regulatory' => $quota ? $quota->regulatory : null,
                    'application_type' => $quota ? $quota->application_type : null,
                ];
            }

            // Equipment
            $eqTypes = HsrmEquipmentType::orderBy('name')->get();
            foreach ($eqTypes as $type) {
                $quota = HsrmEquipmentQuota::where('area_id', $selectedArea->id_area_kerja)
                            ->where('equipment_type_id', $type->id)
                            ->first();

                $activeItems = HsrmEquipment::where('area_id', $selectedArea->id_area_kerja)
                            ->where('equipment_type_id', $type->id)
                            ->where('status_verif', 'verified')
                            ->notExpired()
                            ->sum('total_items');

                $expiredItems = HsrmEquipment::where('area_id', $selectedArea->id_area_kerja)
                            ->where('equipment_type_id', $type->id)
                            ->whereNotNull('expired_date')
                            ->where('expired_date', '<=', now())
                            ->sum('total_items');

                $equipmentData[] = (object) [
                    'type' => $type,
                    'quota' => $quota ? $quota->quota : 0,
                    'budget' => $quota ? $quota->budget : null,
                    'active' => $activeItems,
                    'expired' => $expiredItems,
                    'quota_id' => $quota ? $quota->id : null,
                    'application_type' => $quota ? $quota->application_type : null,
                ];
            }
        }

        return view('hsrm.quotas.index', compact('areas', 'selectedArea', 'certificateData', 'equipmentData', 'isAdmin'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'area_id' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'module' => 'required|in:certificate,equipment',
            'type_id' => 'required|integer',
            'quota' => 'required|integer|min:0',
            'budget' => 'nullable|numeric|min:0',
            'regulatory' => 'nullable|string|max:50',
            'application_type' => 'nullable|string|max:255',
        ]);

        $areaId = $request->area_id;
        $module = $request->module;
        $typeId = $request->type_id;

        $data = [
            'quota' => $request->quota,
            'budget' => $request->budget,
        ];

        if ($request->has('regulatory')) {
            $data['regulatory'] = $request->regulatory;
        }
        if ($request->has('application_type')) {
            $data['application_type'] = $request->application_type;
        }

        if ($module === 'certificate') {
            HsrmCertificateQuota::updateOrCreate(
                ['area_id' => $areaId, 'certificate_type_id' => $typeId],
                $data
            );
        } else {
            HsrmEquipmentQuota::updateOrCreate(
                ['area_id' => $areaId, 'equipment_type_id' => $typeId],
                $data
            );
        }

        return redirect()->back()->with('success', 'Quota updated successfully.');
    }

    /**
     * Export Excel
     * Mode: 'single' atau 'all'
     */
    public function export(Request $request)
    {
        $mode = $request->get('mode', 'single');
        $areaId = $request->get('area_id');

        if ($mode === 'single' && !$areaId) {
            return redirect()->back()->with('error', 'Please select an area first.');
        }

        $filename = 'quota_budget_';
        if ($mode === 'single') {
            $area = AreaKerja::find($areaId);
            $filename .= $area ? $area->nama_area : 'unknown';
        } else {
            $filename .= 'all_areas';
        }
        $filename .= '_' . date('Y-m-d') . '.xlsx';

        return Excel::download(new HsrmQuotaExport($mode, $areaId), $filename);
    }
}
