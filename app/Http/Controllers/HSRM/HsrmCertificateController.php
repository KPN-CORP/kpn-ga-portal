<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\HSRM\HsrmCertificate;
use App\Models\HSRM\HsrmCertificateType;
use App\Models\HSRM\HsrmLog;
use App\Models\AreaKerja;
use App\Models\BisnisUnit;
use App\Models\User;
use App\Helpers\HsrmFileHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Exports\HsrmCertificateExport;
use Maatwebsite\Excel\Facades\Excel;

class HsrmCertificateController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';
        $query = HsrmCertificate::with(['businessUnit', 'area', 'pic', 'creator', 'approver', 'certificateType']);

        if (!$isAdmin) {
            $areaIds = $user->hsrmAreas->pluck('id_area_kerja');
            $query->whereIn('area_id', $areaIds);
        }

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', "%$search%")
                  ->orWhere('nik', 'like', "%$search%");
            });
        }
        if (request('status_verif')) {
            $query->where('status_verif', request('status_verif'));
        }
        if (request('area_id')) {
            $query->where('area_id', request('area_id'));
        }

        // ================== FILTER TANGGAL EXPIRED ==================
        if (request('expired_from')) {
            $query->whereDate('expired_date', '>=', request('expired_from'));
        }
        if (request('expired_to')) {
            $query->whereDate('expired_date', '<=', request('expired_to'));
        }

        $certificates = $query->latest()->get();
        $areas = AreaKerja::all();

        return view('hsrm.certificates.index', compact('certificates', 'areas'));
    }

    public function create()
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';
        $areas = $isAdmin ? AreaKerja::with('businessUnit')->get() : $user->hsrmAreas;
        $businessUnits = BisnisUnit::all();
        $pics = User::whereHas('hsrmAreas')->get();
        $certificateTypes = HsrmCertificateType::orderBy('name')->get();

        return view('hsrm.certificates.create', compact('areas', 'businessUnits', 'pics', 'isAdmin', 'certificateTypes'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';

        $data = $request->validate([
            'business_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'area_id' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'pic_user_id' => 'nullable|exists:users,id',
            'employee_name' => 'required|string|max:255',
            'nik' => 'required|string|max:50',
            'certificate_type_id' => 'required|exists:hsrm_certificate_types,id',
            'instansi_pengurusan' => 'nullable|string|max:255',
            'expired_date' => 'required|date',
            'status_kepemilikan' => 'required|boolean',
            'rekomendasi' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:15360',
        ]);

        if (!$isAdmin) {
            $allowedAreaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
            if (!in_array($data['area_id'], $allowedAreaIds)) {
                return back()->withErrors(['area_id' => 'You are not authorized to create in this area.'])->withInput();
            }
            unset($data['pic_user_id']);
        }

        $data['created_by'] = $user->id;
        $data['status_verif'] = HsrmCertificate::STATUS_PENDING;
        $data['old_attachments'] = [];

        if ($request->hasFile('attachment')) {
            $path = HsrmFileHelper::storeAttachment($request->file('attachment'), 'certificates');
            if ($path) {
                $data['attachment_path'] = $path;
            } else {
                return back()->withErrors(['attachment' => 'Failed to upload file.'])->withInput();
            }
        }

        $cert = HsrmCertificate::create($data);

        HsrmLog::create([
            'user_id' => $user->id,
            'action' => 'create',
            'module' => 'certificate',
            'record_id' => $cert->id,
            'new_data' => $cert->toArray(),
        ]);

        return redirect()->route('hsrm.certificates.index')->with('success', 'Certificate created successfully.');
    }

    public function edit($id)
    {
        $cert = HsrmCertificate::findOrFail($id);
        $this->authorizeEdit($cert);

        $isAdmin = session('hsrm_role') === 'admin';
        $areas = $isAdmin ? AreaKerja::with('businessUnit')->get() : auth()->user()->hsrmAreas;
        $businessUnits = BisnisUnit::all();
        $pics = User::whereHas('hsrmAreas')->get();
        $certificateTypes = HsrmCertificateType::orderBy('name')->get();

        return view('hsrm.certificates.edit', compact('cert', 'areas', 'businessUnits', 'pics', 'isAdmin', 'certificateTypes'));
    }

    public function update(Request $request, $id)
    {
        $cert = HsrmCertificate::findOrFail($id);
        $this->authorizeEdit($cert);

        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';

        $data = $request->validate([
            'business_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'area_id' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'pic_user_id' => 'nullable|exists:users,id',
            'employee_name' => 'required|string|max:255',
            'nik' => 'required|string|max:50',
            'certificate_type_id' => 'required|exists:hsrm_certificate_types,id',
            'instansi_pengurusan' => 'nullable|string|max:255',
            'expired_date' => 'required|date',
            'status_kepemilikan' => 'required|boolean',
            'rekomendasi' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:15360',
        ]);

        if (!$isAdmin) {
            $allowedAreaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
            if (!in_array($data['area_id'], $allowedAreaIds)) {
                return back()->withErrors(['area_id' => 'You are not authorized to edit in this area.'])->withInput();
            }
            unset($data['pic_user_id']);
        }

        $oldData = $cert->toArray();

        if ($request->hasFile('attachment')) {
            $oldPath = $cert->attachment_path;
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                $oldAttachments = $cert->old_attachments ?? [];
                $oldAttachments = HsrmFileHelper::archiveOldAttachment($oldPath, 'certificates', $oldAttachments);
                $data['old_attachments'] = $oldAttachments;
            }

            $newPath = HsrmFileHelper::storeAttachment($request->file('attachment'), 'certificates');
            if ($newPath) {
                $data['attachment_path'] = $newPath;
            } else {
                return back()->withErrors(['attachment' => 'Failed to upload new file.'])->withInput();
            }
        }

        $data['updated_by'] = auth()->id();
        $cert->update($data);

        HsrmLog::create([
            'user_id' => auth()->id(),
            'action' => 'update',
            'module' => 'certificate',
            'record_id' => $cert->id,
            'old_data' => $oldData,
            'new_data' => $cert->toArray(),
        ]);

        return redirect()->route('hsrm.certificates.index')->with('success', 'Certificate updated successfully.');
    }

    public function destroy($id)
    {
        $cert = HsrmCertificate::findOrFail($id);
        $this->authorizeEdit($cert);

        $oldData = $cert->toArray();
        if ($cert->attachment_path) {
            Storage::disk('public')->delete($cert->attachment_path);
        }
        $cert->delete();

        HsrmLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'module' => 'certificate',
            'record_id' => $id,
            'old_data' => $oldData,
        ]);

        return redirect()->route('hsrm.certificates.index')->with('success', 'Certificate deleted.');
    }

    public function approve($id)
    {
        $cert = HsrmCertificate::findOrFail($id);
        if (session('hsrm_role') !== 'admin') {
            abort(403, 'Only admin can approve.');
        }

        $cert->update([
            'status_verif' => HsrmCertificate::STATUS_VERIFIED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        HsrmLog::create([
            'user_id' => auth()->id(),
            'action' => 'approve',
            'module' => 'certificate',
            'record_id' => $cert->id,
            'new_data' => $cert->toArray(),
        ]);

        return redirect()->back()->with('success', 'Certificate approved.');
    }

    public function reject($id)
    {
        $cert = HsrmCertificate::findOrFail($id);
        if (session('hsrm_role') !== 'admin') {
            abort(403, 'Only admin can reject.');
        }

        $cert->update([
            'status_verif' => HsrmCertificate::STATUS_REJECTED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        HsrmLog::create([
            'user_id' => auth()->id(),
            'action' => 'reject',
            'module' => 'certificate',
            'record_id' => $cert->id,
            'new_data' => $cert->toArray(),
        ]);

        return redirect()->back()->with('success', 'Certificate rejected.');
    }

    public function show($id)
    {
        $cert = HsrmCertificate::with([
            'businessUnit',
            'area',
            'pic',
            'creator',
            'approver',
            'certificateType'
        ])->findOrFail($id);

        $this->authorizeView($cert);

        return view('hsrm.certificates.show', compact('cert'));
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
            new HsrmCertificateExport($filters, $isAdmin, $areaIds),
            'certificates-report.xlsx'
        );
    }

    private function authorizeView($cert)
    {
        $user = auth()->user();
        if (session('hsrm_role') === 'admin') {
            return;
        }
        $areaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
        if (!in_array($cert->area_id, $areaIds)) {
            abort(403, 'You are not authorized to view this certificate.');
        }
    }

    private function authorizeEdit($cert)
    {
        $user = auth()->user();
        if (session('hsrm_role') === 'admin') {
            return;
        }
        $areaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
        if (!in_array($cert->area_id, $areaIds)) {
            abort(403, 'You are not authorized to edit this certificate.');
        }
    }
}