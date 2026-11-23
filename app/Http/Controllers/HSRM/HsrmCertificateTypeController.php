<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Helpers\HsrmRole;
use App\Models\HSRM\HsrmCertificateType;
use Illuminate\Http\Request;

class HsrmCertificateTypeController extends Controller
{
    public function __construct()
    {
        // PIC boleh melihat daftar (index), tapi hanya admin yang boleh
        // menambah, mengubah, atau menghapus certificate type.
        $this->middleware(function ($request, $next) {
            if (!in_array(session('hsrm_role'), HsrmRole::ALL, true)) {
                abort(403, 'Unauthorized.');
            }
            return $next($request);
        });

        $this->middleware(function ($request, $next) {
            if ($request->route()->getName() !== 'hsrm.certificate-types.index' && !HsrmRole::isAdmin()) {
                abort(403, 'Only admin can manage certificate types. PIC and management have view-only access.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $types = HsrmCertificateType::orderBy('name')->get();
        return view('hsrm.certificate_types.index', compact('types'));
    }

    public function create()
    {
        return view('hsrm.certificate_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:hsrm_certificate_types,name',
            'description' => 'nullable|string',
        ]);

        HsrmCertificateType::create($request->all());

        return redirect()->route('hsrm.certificate-types.index')
            ->with('success', 'Certificate type added successfully.');
    }

    public function edit($id)
    {
        $type = HsrmCertificateType::findOrFail($id);
        return view('hsrm.certificate_types.edit', compact('type'));
    }

    public function update(Request $request, $id)
    {
        $type = HsrmCertificateType::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255|unique:hsrm_certificate_types,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $type->update($request->all());

        return redirect()->route('hsrm.certificate-types.index')
            ->with('success', 'Certificate type updated successfully.');
    }

    public function destroy($id)
    {
        $type = HsrmCertificateType::findOrFail($id);

        if ($type->certificates()->count() > 0) {
            return back()->with('error', 'This type is in use and cannot be deleted.');
        }

        $quotaCount = $type->quotas()->count();
        if ($quotaCount > 0) {
            return back()->with('error', "This type still has quota/budget settings configured in {$quotaCount} area(s). Please remove those quota settings first from the Budget & Quota page before deleting this type.");
        }

        $type->delete();
        return redirect()->route('hsrm.certificate-types.index')
            ->with('success', 'Certificate type deleted successfully.');
    }
}