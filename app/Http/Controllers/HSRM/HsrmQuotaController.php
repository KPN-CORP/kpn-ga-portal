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
        $this->middleware(function ($request, $next) {
            if (session('hsrm_role') !== 'admin') {
                abort(403, 'Only admin can manage quotas.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $areas = AreaKerja::orderBy('nama_area')->get();
        $selectedArea = $request->get('area_id') ? AreaKerja::find($request->area_id) : null;

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
                            ->where('expired_date', '>', now())
                            ->count();

                $expired = HsrmCertificate::where('area_id', $selectedArea->id_area_kerja)
                            ->where('certificate_type_id', $type->id)
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
                            ->where('expired_date', '>', now())
                            ->sum('total_items');

                $expiredItems = HsrmEquipment::where('area_id', $selectedArea->id_area_kerja)
                            ->where('equipment_type_id', $type->id)
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

        return view('hsrm.quotas.index', compact('areas', 'selectedArea', 'certificateData', 'equipmentData'));
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