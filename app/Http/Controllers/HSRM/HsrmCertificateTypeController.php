<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\HSRM\HsrmCertificateType;
use Illuminate\Http\Request;

class HsrmCertificateTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (session('hsrm_role') !== 'admin') {
                abort(403, 'Only admin can manage certificate types.');
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
        $type->delete();
        return redirect()->route('hsrm.certificate-types.index')
            ->with('success', 'Certificate type deleted successfully.');
    }
}