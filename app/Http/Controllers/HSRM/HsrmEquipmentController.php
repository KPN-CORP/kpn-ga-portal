<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\HSRM\HsrmEquipment;
use App\Models\HSRM\HsrmEquipmentType;
use App\Models\HSRM\HsrmLog;
use App\Models\AreaKerja;
use App\Models\BisnisUnit;
use App\Models\User;
use App\Helpers\HsrmFileHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Exports\HsrmEquipmentExport;
use Maatwebsite\Excel\Facades\Excel;

class HsrmEquipmentController extends Controller
{
    public function index(Request $request, $filter = null)
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';
        $query = HsrmEquipment::with(['businessUnit', 'area', 'pic', 'creator', 'approver', 'equipmentType']);

        if (!$isAdmin) {
            $areaIds = $user->hsrmAreas->pluck('id_area_kerja');
            $query->whereIn('area_id', $areaIds);
        }

        // ===== FILTER FROM DASHBOARD CLICK =====
        if ($filter) {
            switch ($filter) {
                case 'active':
                    $query->where('expired_date', '>', now()->addDays(30))
                          ->where('status_verif', 'verified');
                    break;
                case 'warning':
                    $query->where('expired_date', '<=', now()->addDays(30))
                          ->where('expired_date', '>', now());
                    break;
                case 'expired':
                    $query->where('expired_date', '<=', now());
                    break;
                case 'pending':
                    $query->where('status_verif', 'pending');
                    break;
                case 'total':
                    // no filter
                    break;
                default:
                    abort(404);
            }
        }

        // ===== SEARCH & FILTER =====
        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('capacity', 'like', "%$search%");
            });
        }
        if (request('status_verif')) {
            $query->where('status_verif', request('status_verif'));
        }
        if (request('area_id')) {
            $query->where('area_id', request('area_id'));
        }
        if (request('expired_from')) {
            $query->whereDate('expired_date', '>=', request('expired_from'));
        }
        if (request('expired_to')) {
            $query->whereDate('expired_date', '<=', request('expired_to'));
        }

        $equipments = $query->latest()->get();
        $areas = AreaKerja::all();

        return view('hsrm.equipments.index', compact('equipments', 'areas'));
    }

    public function create()
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';
        $areas = $isAdmin ? AreaKerja::with('businessUnit')->get() : $user->hsrmAreas;
        $businessUnits = BisnisUnit::all();
        $pics = User::whereHas('hsrmAreas')->get();
        $equipmentTypes = HsrmEquipmentType::orderBy('name')->get();

        return view('hsrm.equipments.create', compact('areas', 'businessUnits', 'pics', 'isAdmin', 'equipmentTypes'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';

        $data = $request->validate([
            'business_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'area_id' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'pic_user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'equipment_type_id' => 'required|exists:hsrm_equipment_types,id',
            'capacity' => 'required|string|max:50',
            'location' => 'nullable|string|max:255',
            'expired_date' => 'required|date',
            'status_kepemilikan' => 'required|boolean',
            'rekomendasi' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:15360',
        ]);

        if (!$isAdmin) {
            $allowedAreaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
            if (!in_array($data['area_id'], $allowedAreaIds)) {
                return back()->withErrors(['area_id' => 'You are not authorized to create in this area.'])->withInput();
            }
            unset($data['pic_user_id']);
        }

        $data['created_by'] = $user->id;
        $data['status_verif'] = HsrmEquipment::STATUS_PENDING;
        $data['old_attachments'] = [];

        if ($request->hasFile('photo')) {
            $path = HsrmFileHelper::storeAttachment($request->file('photo'), 'equipments');
            if ($path) {
                $data['photo_path'] = $path;
            } else {
                return back()->withErrors(['photo' => 'Failed to upload photo.'])->withInput();
            }
        }

        $equipment = HsrmEquipment::create($data);

        HsrmLog::create([
            'user_id' => $user->id,
            'action' => 'create',
            'module' => 'equipment',
            'record_id' => $equipment->id,
            'new_data' => $equipment->toArray(),
        ]);

        return redirect()->route('hsrm.equipments.index')->with('success', 'Equipment created successfully.');
    }

    public function edit($id)
    {
        $equipment = HsrmEquipment::findOrFail($id);
        $this->authorizeEdit($equipment);

        $isAdmin = session('hsrm_role') === 'admin';
        $areas = $isAdmin ? AreaKerja::with('businessUnit')->get() : auth()->user()->hsrmAreas;
        $businessUnits = BisnisUnit::all();
        $pics = User::whereHas('hsrmAreas')->get();
        $equipmentTypes = HsrmEquipmentType::orderBy('name')->get();

        return view('hsrm.equipments.edit', compact('equipment', 'areas', 'businessUnits', 'pics', 'isAdmin', 'equipmentTypes'));
    }

    public function update(Request $request, $id)
    {
        $equipment = HsrmEquipment::findOrFail($id);
        $this->authorizeEdit($equipment);

        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';

        $data = $request->validate([
            'business_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'area_id' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'pic_user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'equipment_type_id' => 'required|exists:hsrm_equipment_types,id',
            'capacity' => 'required|string|max:50',
            'location' => 'nullable|string|max:255',
            'expired_date' => 'required|date',
            'status_kepemilikan' => 'required|boolean',
            'rekomendasi' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:15360',
        ]);

        if (!$isAdmin) {
            $allowedAreaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
            if (!in_array($data['area_id'], $allowedAreaIds)) {
                return back()->withErrors(['area_id' => 'You are not authorized to edit in this area.'])->withInput();
            }
            unset($data['pic_user_id']);
        }

        $oldData = $equipment->toArray();

        if ($request->hasFile('photo')) {
            $oldPath = $equipment->photo_path;
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                $oldAttachments = $equipment->old_attachments ?? [];
                $oldAttachments = HsrmFileHelper::archiveOldAttachment($oldPath, 'equipments', $oldAttachments);
                $data['old_attachments'] = $oldAttachments;
            }

            $newPath = HsrmFileHelper::storeAttachment($request->file('photo'), 'equipments');
            if ($newPath) {
                $data['photo_path'] = $newPath;
            } else {
                return back()->withErrors(['photo' => 'Failed to upload new photo.'])->withInput();
            }
        }

        $data['updated_by'] = auth()->id();
        $equipment->update($data);

        HsrmLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'module' => 'equipment',
            'record_id' => $equipment->id,
            'old_data' => $oldData,
            'new_data' => $equipment->toArray(),
        ]);

        return redirect()->route('hsrm.equipments.index')->with('success', 'Equipment updated successfully.');
    }

    public function destroy($id)
    {
        $equipment = HsrmEquipment::findOrFail($id);
        $this->authorizeEdit($equipment);

        $oldData = $equipment->toArray();
        if ($equipment->photo_path) {
            Storage::disk('public')->delete($equipment->photo_path);
        }
        $equipment->delete();

        HsrmLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'module' => 'equipment',
            'record_id' => $id,
            'old_data' => $oldData,
        ]);

        return redirect()->route('hsrm.equipments.index')->with('success', 'Equipment deleted.');
    }

    public function approve($id)
    {
        $equipment = HsrmEquipment::findOrFail($id);
        $this->authorizeApprove($equipment);

        $equipment->update([
            'status_verif' => HsrmEquipment::STATUS_VERIFIED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        HsrmLog::create([
            'user_id' => auth()->id(),
            'action' => 'approve',
            'module' => 'equipment',
            'record_id' => $equipment->id,
            'new_data' => $equipment->toArray(),
        ]);

        return redirect()->back()->with('success', 'Equipment approved.');
    }

    public function reject($id)
    {
        $equipment = HsrmEquipment::findOrFail($id);
        $this->authorizeApprove($equipment);

        $equipment->update([
            'status_verif' => HsrmEquipment::STATUS_REJECTED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        HsrmLog::create([
            'user_id' => auth()->id(),
            'action' => 'reject',
            'module' => 'equipment',
            'record_id' => $equipment->id,
            'new_data' => $equipment->toArray(),
        ]);

        return redirect()->back()->with('success', 'Equipment rejected.');
    }

    public function show($id)
    {
        $equipment = HsrmEquipment::with(['businessUnit', 'area', 'pic', 'creator', 'approver', 'equipmentType'])
            ->findOrFail($id);

        $this->authorizeView($equipment);

        return view('hsrm.equipments.show', compact('equipment'));
    }

    public function export()
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';
        $areaIds = $isAdmin ? [] : $user->hsrmAreas->pluck('id_area_kerja')->toArray();

        $filters = [
            'status_verif' => request('status_verif'),
            'area_id' => request('area_id'),
            'expired_from' => request('expired_from'),
            'expired_to' => request('expired_to'),
        ];

        return Excel::download(
            new HsrmEquipmentExport($filters, $isAdmin, $areaIds),
            'equipments-report.xlsx'
        );
    }

    // ===== AUTHORIZATION METHODS =====
    private function authorizeView($equipment)
    {
        $user = auth()->user();
        if (session('hsrm_role') === 'admin') {
            return;
        }
        $areaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
        if (!in_array($equipment->area_id, $areaIds)) {
            abort(403, 'You are not authorized to view this equipment.');
        }
    }

    private function authorizeEdit($equipment)
    {
        $user = auth()->user();
        if (session('hsrm_role') === 'admin') {
            return;
        }
        if (!$user->canEditInArea($equipment->area_id)) {
            abort(403, 'You are not authorized to edit this equipment.');
        }
    }

    private function authorizeApprove($equipment)
    {
        $user = auth()->user();
        if (!$user->canApproveInArea($equipment->area_id)) {
            abort(403, 'You are not authorized to approve this equipment.');
        }
    }
}