<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\HSRM\HsrmEquipment;
use App\Models\HSRM\HsrmEquipmentQuota;
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
                    break;
                default:
                    abort(404);
            }
        }

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

        // 🔽 FILTER TYPE
        if (request('equipment_type_id')) {
            $query->where('equipment_type_id', request('equipment_type_id'));
        }

        // 🔽 PAGINATION (15 item per halaman)
        $equipments = $query->latest()->paginate(15);

        $areas = AreaKerja::all();
        $equipmentTypes = HsrmEquipmentType::orderBy('name')->get();

        return view('hsrm.equipments.index', compact('equipments', 'areas', 'equipmentTypes'));
    }

    public function create()
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';
        $areas = $isAdmin ? AreaKerja::with('businessUnit')->get() : $user->hsrmAreas;
        $businessUnits = BisnisUnit::all();
        $pics = User::whereHas('hsrmAreas')->get();
        $equipmentTypes = HsrmEquipmentType::orderBy('name')->get();

        // Ambil semua quota untuk area
        $quotaData = [];
        if ($isAdmin) {
            $allQuotas = HsrmEquipmentQuota::with(['area', 'equipmentType'])->get();
            foreach ($allQuotas as $q) {
                $key = $q->area_id . '_' . $q->equipment_type_id;
                $quotaData[$key] = $q->quota;
            }
        } else {
            $areaIds = $areas->pluck('id_area_kerja')->toArray();
            $allQuotas = HsrmEquipmentQuota::whereIn('area_id', $areaIds)->get();
            foreach ($allQuotas as $q) {
                $key = $q->area_id . '_' . $q->equipment_type_id;
                $quotaData[$key] = $q->quota;
            }
        }

        return view('hsrm.equipments.create', compact('areas', 'businessUnits', 'pics', 'isAdmin', 'equipmentTypes', 'quotaData'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';

        // Validasi dasar
        $rules = [
            'business_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'area_id' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'pic_user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'capacity' => 'required|string|max:50',
            'total_items' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'expired_date' => 'required|date',
            'status_kepemilikan' => 'required|boolean',
            'rekomendasi' => 'nullable|in:recommended,not_recommended,valid',
            'notes' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:15360',
        ];

        // Validasi custom atau pilih dari dropdown
        if ($request->filled('custom_equipment_type')) {
            $rules['custom_equipment_type'] = 'required|string|max:255|unique:hsrm_equipment_types,name';
            $request->validate($rules);
            $data = $request->all();
            $data['equipment_type_id'] = null;
        } else {
            $rules['equipment_type_id'] = 'required|exists:hsrm_equipment_types,id';
            $request->validate($rules);
            $data = $request->all();
            $data['custom_equipment_type'] = null;
        }

        // Otorisasi area untuk PIC
        if (!$isAdmin) {
            $allowedAreaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
            if (!in_array($data['area_id'], $allowedAreaIds)) {
                return back()->withErrors(['area_id' => 'You are not authorized to create in this area.'])->withInput();
            }
            unset($data['pic_user_id']);
        }

        // Validasi kuota untuk tipe yang sudah ada (bukan custom)
        if (!empty($data['equipment_type_id'])) {
            $quota = HsrmEquipmentQuota::where('area_id', $data['area_id'])
                        ->where('equipment_type_id', $data['equipment_type_id'])
                        ->first();

            if ($quota && $quota->quota > 0) {
                $activeItems = HsrmEquipment::where('area_id', $data['area_id'])
                                ->where('equipment_type_id', $data['equipment_type_id'])
                                ->where('status_verif', 'verified')
                                ->where('expired_date', '>', now())
                                ->sum('total_items');

                $newTotal = $activeItems + ($data['total_items'] ?? 1);
                if ($newTotal > $quota->quota) {
                    return back()->withErrors([
                        'equipment_type_id' => 'Kuota untuk tipe peralatan ini sudah penuh (maksimal '.$quota->quota.' item aktif, saat ini '.$activeItems.' aktif).'
                    ])->withInput();
                }
            }
        }

        $data['created_by'] = $user->id;
        $data['status_verif'] = HsrmEquipment::STATUS_PENDING;
        $data['old_attachments'] = [];
        $data['total_items'] = 1; // selalu 1

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

        // Ambil quota data
        $quotaData = [];
        $areaIds = $areas->pluck('id_area_kerja')->toArray();
        if (!empty($areaIds)) {
            $allQuotas = HsrmEquipmentQuota::whereIn('area_id', $areaIds)->get();
            foreach ($allQuotas as $q) {
                $key = $q->area_id . '_' . $q->equipment_type_id;
                $quotaData[$key] = $q->quota;
            }
        }

        return view('hsrm.equipments.edit', compact('equipment', 'areas', 'businessUnits', 'pics', 'isAdmin', 'equipmentTypes', 'quotaData'));
    }

    public function update(Request $request, $id)
    {
        $equipment = HsrmEquipment::findOrFail($id);
        $this->authorizeEdit($equipment);

        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';

        // Validasi
        $rules = [
            'business_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'area_id' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'pic_user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'capacity' => 'required|string|max:50',
            'total_items' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'expired_date' => 'required|date',
            'status_kepemilikan' => 'required|boolean',
            'rekomendasi' => 'nullable|in:recommended,not_recommended,valid',
            'notes' => 'nullable|string',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png|max:15360',
        ];

        if ($request->filled('custom_equipment_type')) {
            $rules['custom_equipment_type'] = 'required|string|max:255|unique:hsrm_equipment_types,name';
            $request->validate($rules);
            $data = $request->all();
            $data['equipment_type_id'] = null;
        } else {
            $rules['equipment_type_id'] = 'required|exists:hsrm_equipment_types,id';
            $request->validate($rules);
            $data = $request->all();
            $data['custom_equipment_type'] = null;
        }

        if (!$isAdmin) {
            $allowedAreaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
            if (!in_array($data['area_id'], $allowedAreaIds)) {
                return back()->withErrors(['area_id' => 'You are not authorized to edit in this area.'])->withInput();
            }
            unset($data['pic_user_id']);
        }

        // Validasi kuota jika area atau tipe berubah
        $areaChanged = ($equipment->area_id != $data['area_id']);
        $typeChanged = ($equipment->equipment_type_id != $data['equipment_type_id']);

        if (($areaChanged || $typeChanged) && !empty($data['equipment_type_id'])) {
            $quota = HsrmEquipmentQuota::where('area_id', $data['area_id'])
                        ->where('equipment_type_id', $data['equipment_type_id'])
                        ->first();

            if ($quota && $quota->quota > 0) {
                $activeItems = HsrmEquipment::where('area_id', $data['area_id'])
                                ->where('equipment_type_id', $data['equipment_type_id'])
                                ->where('status_verif', 'verified')
                                ->where('expired_date', '>', now())
                                ->where('id', '!=', $equipment->id)
                                ->sum('total_items');

                $newTotal = $activeItems + ($data['total_items'] ?? 1);
                if ($newTotal > $quota->quota) {
                    return back()->withErrors([
                        'equipment_type_id' => 'Kuota untuk tipe peralatan ini sudah penuh (maksimal '.$quota->quota.' item aktif).'
                    ])->withInput();
                }
            }
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

        $data['total_items'] = 1; // selalu 1
        $data['status_verif'] = HsrmEquipment::STATUS_PENDING;
        $data['approved_by'] = null;
        $data['approved_at'] = null;
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

        return redirect()->route('hsrm.equipments.index')->with('success', 'Equipment updated successfully. It will need approval again.');
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

        // Jika ada custom type, buat tipe baru
        if ($equipment->custom_equipment_type) {
            $existing = HsrmEquipmentType::where('name', $equipment->custom_equipment_type)->first();
            if (!$existing) {
                $newType = HsrmEquipmentType::create([
                    'name' => $equipment->custom_equipment_type,
                    'description' => 'Auto-created from custom equipment',
                ]);
                $equipment->equipment_type_id = $newType->id;
                $equipment->custom_equipment_type = null;
            } else {
                $equipment->equipment_type_id = $existing->id;
                $equipment->custom_equipment_type = null;
            }
        }

        // Validasi kuota untuk tipe yang sudah ada (bukan custom)
        if ($equipment->equipment_type_id) {
            $quota = HsrmEquipmentQuota::where('area_id', $equipment->area_id)
                        ->where('equipment_type_id', $equipment->equipment_type_id)
                        ->first();

            if ($quota && $quota->quota > 0) {
                $activeItems = HsrmEquipment::where('area_id', $equipment->area_id)
                                ->where('equipment_type_id', $equipment->equipment_type_id)
                                ->where('status_verif', 'verified')
                                ->where('expired_date', '>', now())
                                ->where('id', '!=', $equipment->id)
                                ->sum('total_items');

                $newTotal = $activeItems + ($equipment->total_items ?? 1);
                if ($newTotal > $quota->quota) {
                    return back()->withErrors([
                        'error' => 'Kuota untuk tipe peralatan ini sudah penuh (maksimal '.$quota->quota.' item aktif, saat ini '.$activeItems.' aktif). Tidak bisa approve.'
                    ]);
                }
            }
        }

        $equipment->status_verif = HsrmEquipment::STATUS_VERIFIED;
        $equipment->approved_by = auth()->id();
        $equipment->approved_at = now();
        $equipment->save();

        HsrmLog::create([
            'user_id' => auth()->id(),
            'action' => 'approve',
            'module' => 'equipment',
            'record_id' => $equipment->id,
            'new_data' => $equipment->toArray(),
        ]);

        return redirect()->back()->with('success', 'Equipment approved. Custom type has been added if any.');
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
            'equipment_type_id' => request('equipment_type_id'), // tambahkan untuk export
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