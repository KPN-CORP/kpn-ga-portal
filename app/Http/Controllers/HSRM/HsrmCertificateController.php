<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\HSRM\HsrmCertificate;
use App\Models\HSRM\HsrmCertificateQuota;
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
    public function index(Request $request, $filter = null)
    {
        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';
        $query = HsrmCertificate::with(['businessUnit', 'area', 'pic', 'creator', 'approver', 'certificateType']);

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

        // Ambil semua quota untuk area-area yang dimiliki user (atau semua jika admin)
        $quotaData = [];
        if ($isAdmin) {
            $allQuotas = HsrmCertificateQuota::with(['area', 'certificateType'])->get();
            foreach ($allQuotas as $q) {
                $key = $q->area_id . '_' . $q->certificate_type_id;
                $quotaData[$key] = $q->quota;
            }
        } else {
            $areaIds = $areas->pluck('id_area_kerja')->toArray();
            $allQuotas = HsrmCertificateQuota::whereIn('area_id', $areaIds)->get();
            foreach ($allQuotas as $q) {
                $key = $q->area_id . '_' . $q->certificate_type_id;
                $quotaData[$key] = $q->quota;
            }
        }

        return view('hsrm.certificates.create', compact('areas', 'businessUnits', 'pics', 'isAdmin', 'certificateTypes', 'quotaData'));
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
            'employee_name' => 'required|string|max:255',
            'nik' => 'required|string|max:50',
            'instansi_pengurusan' => 'nullable|string|max:255',
            'expired_date' => 'required|date',
            'status_kepemilikan' => 'required|boolean',
            'rekomendasi' => 'nullable|in:recommended,not_recommended,valid',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:15360',
        ];

        // Validasi: certificate_type_id atau custom_certificate_type harus diisi
        if ($request->filled('custom_certificate_type')) {
            $rules['custom_certificate_type'] = 'required|string|max:255|unique:hsrm_certificate_types,name';
            $request->validate($rules);
            $data = $request->all();
            $data['certificate_type_id'] = null; // kosongkan jika custom
        } else {
            $rules['certificate_type_id'] = 'required|exists:hsrm_certificate_types,id';
            $request->validate($rules);
            $data = $request->all();
            $data['custom_certificate_type'] = null;
        }

        // Otorisasi area untuk non-admin
        if (!$isAdmin) {
            $allowedAreaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
            if (!in_array($data['area_id'], $allowedAreaIds)) {
                return back()->withErrors(['area_id' => 'You are not authorized to create in this area.'])->withInput();
            }
            unset($data['pic_user_id']);
        }

        // Validasi kuota hanya jika certificate_type_id ada (bukan custom)
        if (!empty($data['certificate_type_id'])) {
            $quota = HsrmCertificateQuota::where('area_id', $data['area_id'])
                        ->where('certificate_type_id', $data['certificate_type_id'])
                        ->first();

            if ($quota && $quota->quota > 0) {
                $activeCount = HsrmCertificate::where('area_id', $data['area_id'])
                                ->where('certificate_type_id', $data['certificate_type_id'])
                                ->where('status_verif', 'verified')
                                ->where('expired_date', '>', now())
                                ->count();

                if ($activeCount >= $quota->quota) {
                    return back()->withErrors([
                        'certificate_type_id' => 'Kuota untuk tipe sertifikat ini sudah penuh (maksimal '.$quota->quota.').'
                    ])->withInput();
                }
            }
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

        // Ambil quota data untuk info
        $quotaData = [];
        if ($isAdmin) {
            $allQuotas = HsrmCertificateQuota::with(['area', 'certificateType'])->get();
            foreach ($allQuotas as $q) {
                $key = $q->area_id . '_' . $q->certificate_type_id;
                $quotaData[$key] = $q->quota;
            }
        } else {
            $areaIds = $areas->pluck('id_area_kerja')->toArray();
            $allQuotas = HsrmCertificateQuota::whereIn('area_id', $areaIds)->get();
            foreach ($allQuotas as $q) {
                $key = $q->area_id . '_' . $q->certificate_type_id;
                $quotaData[$key] = $q->quota;
            }
        }

        return view('hsrm.certificates.edit', compact('cert', 'areas', 'businessUnits', 'pics', 'isAdmin', 'certificateTypes', 'quotaData'));
    }

    public function update(Request $request, $id)
    {
        $cert = HsrmCertificate::findOrFail($id);
        $this->authorizeEdit($cert);

        $user = auth()->user();
        $isAdmin = session('hsrm_role') === 'admin';

        $rules = [
            'business_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'area_id' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'pic_user_id' => 'nullable|exists:users,id',
            'employee_name' => 'required|string|max:255',
            'nik' => 'required|string|max:50',
            'instansi_pengurusan' => 'nullable|string|max:255',
            'expired_date' => 'required|date',
            'status_kepemilikan' => 'required|boolean',
            'rekomendasi' => 'nullable|in:recommended,not_recommended,valid',
            'notes' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:15360',
        ];

        if ($request->filled('custom_certificate_type')) {
            $rules['custom_certificate_type'] = 'required|string|max:255|unique:hsrm_certificate_types,name';
            $request->validate($rules);
            $data = $request->all();
            $data['certificate_type_id'] = null;
        } else {
            $rules['certificate_type_id'] = 'required|exists:hsrm_certificate_types,id';
            $request->validate($rules);
            $data = $request->all();
            $data['custom_certificate_type'] = null;
        }

        if (!$isAdmin) {
            $allowedAreaIds = $user->hsrmAreas->pluck('id_area_kerja')->toArray();
            if (!in_array($data['area_id'], $allowedAreaIds)) {
                return back()->withErrors(['area_id' => 'You are not authorized to edit in this area.'])->withInput();
            }
            unset($data['pic_user_id']);
        }

        // Validasi kuota jika area atau tipe berubah dan bukan custom
        $areaChanged = ($cert->area_id != $data['area_id']);
        $typeChanged = ($cert->certificate_type_id != $data['certificate_type_id']);

        if (!empty($data['certificate_type_id']) && ($areaChanged || $typeChanged)) {
            $quota = HsrmCertificateQuota::where('area_id', $data['area_id'])
                        ->where('certificate_type_id', $data['certificate_type_id'])
                        ->first();

            if ($quota && $quota->quota > 0) {
                $activeCount = HsrmCertificate::where('area_id', $data['area_id'])
                                ->where('certificate_type_id', $data['certificate_type_id'])
                                ->where('status_verif', 'verified')
                                ->where('expired_date', '>', now())
                                ->where('id', '!=', $cert->id)
                                ->count();

                if ($activeCount >= $quota->quota) {
                    return back()->withErrors([
                        'certificate_type_id' => 'Kuota untuk tipe sertifikat ini sudah penuh (maksimal '.$quota->quota.').'
                    ])->withInput();
                }
            }
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

        // Reset approval status
        $data['status_verif'] = HsrmCertificate::STATUS_PENDING;
        $data['approved_by'] = null;
        $data['approved_at'] = null;
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

        return redirect()->route('hsrm.certificates.index')->with('success', 'Certificate updated successfully. It will need approval again.');
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
        $this->authorizeApprove($cert);

        // Jika ada custom type, buat tipe baru
        if ($cert->custom_certificate_type) {
            // Cek apakah sudah ada (unique)
            $existing = HsrmCertificateType::where('name', $cert->custom_certificate_type)->first();
            if (!$existing) {
                $newType = HsrmCertificateType::create([
                    'name' => $cert->custom_certificate_type,
                    'description' => 'Auto-created from custom certificate on ' . now()->format('d-m-Y H:i'),
                ]);
                $cert->certificate_type_id = $newType->id;
                $cert->custom_certificate_type = null;
            } else {
                // Jika sudah ada, gunakan yang sudah ada dan kosongkan custom
                $cert->certificate_type_id = $existing->id;
                $cert->custom_certificate_type = null;
            }
        }

        $cert->status_verif = HsrmCertificate::STATUS_VERIFIED;
        $cert->approved_by = auth()->id();
        $cert->approved_at = now();
        $cert->save();

        HsrmLog::create([
            'user_id' => auth()->id(),
            'action' => 'approve',
            'module' => 'certificate',
            'record_id' => $cert->id,
            'new_data' => $cert->toArray(),
        ]);

        return redirect()->back()->with('success', 'Certificate approved. Custom type has been added if any.');
    }

    public function reject($id)
    {
        $cert = HsrmCertificate::findOrFail($id);
        $this->authorizeApprove($cert);

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

    // ===== AUTHORIZATION METHODS =====
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
        if (!$user->canEditInArea($cert->area_id)) {
            abort(403, 'You are not authorized to edit this certificate.');
        }
    }

    private function authorizeApprove($cert)
    {
        $user = auth()->user();
        if (!$user->canApproveInArea($cert->area_id)) {
            abort(403, 'You are not authorized to approve this certificate.');
        }
    }
}