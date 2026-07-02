<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\HSRM\HsrmCertificate;
use App\Models\HSRM\HsrmEquipment;

class HsrmApprovalController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';

        // Certificates pending
        $certQuery = HsrmCertificate::where('status_verif', 'pending');
        if (!$isAdmin) {
            $areaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
            $certQuery->whereIn('area_id', $areaIds);
        }
        $certificates = $certQuery->get();

        // Equipments pending
        $eqQuery = HsrmEquipment::where('status_verif', 'pending');
        if (!$isAdmin) {
            $areaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
            $eqQuery->whereIn('area_id', $areaIds);
        }
        $equipments = $eqQuery->get();

        return view('hsrm.approvals.index', compact('certificates', 'equipments'));
    }
}