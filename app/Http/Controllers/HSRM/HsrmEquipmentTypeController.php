<?php

namespace App\Http\Controllers\HSRM;

use App\Http\Controllers\Controller;
use App\Models\HSRM\HsrmEquipmentType;
use Illuminate\Http\Request;

class HsrmEquipmentTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (session('hsrm_role') !== 'admin') {
                abort(403, 'Only admin can manage equipment types.');
            }
            return $next($request);
        });
    }

    public function index()
    {
        $types = HsrmEquipmentType::orderBy('name')->get();
        return view('hsrm.equipment_types.index', compact('types'));
    }

    public function create()
    {
        return view('hsrm.equipment_types.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:hsrm_equipment_types,name',
            'description' => 'nullable|string',
        ]);
        HsrmEquipmentType::create($request->all());
        return redirect()->route('hsrm.equipment-types.index')->with('success', 'Equipment type added.');
    }

    public function edit($id)
    {
        $type = HsrmEquipmentType::findOrFail($id);
        return view('hsrm.equipment_types.edit', compact('type'));
    }

    public function update(Request $request, $id)
    {
        $type = HsrmEquipmentType::findOrFail($id);
        $request->validate([
            'name' => 'required|string|max:255|unique:hsrm_equipment_types,name,' . $id,
            'description' => 'nullable|string',
        ]);
        $type->update($request->all());
        return redirect()->route('hsrm.equipment-types.index')->with('success', 'Equipment type updated.');
    }

    public function destroy($id)
    {
        $type = HsrmEquipmentType::findOrFail($id);
        if ($type->equipments()->count() > 0) {
            return back()->with('error', 'Type is in use and cannot be deleted.');
        }
        $type->delete();
        return redirect()->route('hsrm.equipment-types.index')->with('success', 'Equipment type deleted.');
    }
}