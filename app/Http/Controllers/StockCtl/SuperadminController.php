<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\AreaKerja;
use App\Models\StockCtl\UserProfil;
use App\Models\BisnisUnit;
use App\Models\User;

class SuperadminController extends Controller
{
    // Area Kerja CRUD
    public function index()
    {
        $areas = AreaKerja::with('bisnisUnit')->get();
        return view('stock-ctl.superadmin.area_kerja.index', compact('areas'));
    }

    public function create()
    {
        $bisnisUnits = BisnisUnit::all();
        return view('stock-ctl.superadmin.area_kerja.create', compact('bisnisUnits'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_area' => 'required',
            'id_bisnis_unit' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
        ]);

        AreaKerja::create($request->all());
        return redirect()->route('stock-ctl.area.index')->with('success', 'Area kerja ditambahkan.');
    }

    public function edit($id)
    {
        $area = AreaKerja::findOrFail($id);
        $bisnisUnits = BisnisUnit::all();
        return view('stock-ctl.superadmin.area_kerja.edit', compact('area', 'bisnisUnits'));
    }

    public function update(Request $request, $id)
    {
        $area = AreaKerja::findOrFail($id);
        $request->validate([
            'nama_area' => 'required',
            'id_bisnis_unit' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
        ]);

        $area->update($request->all());
        return redirect()->route('stock-ctl.area.index')->with('success', 'Area kerja diperbarui.');
    }

    public function destroy($id)
    {
        $area = AreaKerja::findOrFail($id);
        if ($area->stok()->exists() || UserProfil::where('id_area_kerja', $id)->exists()) {
            return back()->withErrors('Area tidak dapat dihapus karena masih digunakan.');
        }
        $area->delete();
        return redirect()->route('stock-ctl.area.index')->with('success', 'Area kerja dihapus.');
    }

    // User Profil
    public function userProfilIndex(Request $request)
    {
        $query = UserProfil::with('user', 'bisnisUnit', 'areaKerja', 'approver');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $profils = $query->paginate(15);
        $bisnisUnits = BisnisUnit::all();
        $areas = AreaKerja::all();
        $users = User::all();

        return view('stock-ctl.superadmin.user_profil.index', compact('profils', 'bisnisUnits', 'areas', 'users'));
    }

    public function getUserProfil($id)
    {
        $profil = UserProfil::with('user', 'bisnisUnit', 'areaKerja', 'approver')->findOrFail($id);
        return response()->json($profil);
    }

    public function userProfilUpdate(Request $request, $id)
    {
        $request->validate([
            'id_bisnis_unit' => 'nullable|exists:tb_bisnis_unit,id_bisnis_unit',
            'id_area_kerja' => 'nullable|exists:stock_ctl_area_kerja,id_area_kerja',
            'id_approver' => 'nullable|exists:users,id',
        ]);

        $profil = UserProfil::findOrFail($id);
        $profil->update($request->only(['id_bisnis_unit', 'id_area_kerja', 'id_approver']));
        return redirect()->route('stock-ctl.user-profil.index')->with('success', 'Profil user diperbarui.');
    }
}