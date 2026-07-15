<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Voucher;
use App\Models\BisnisUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    /**
     * Ambil business_unit_id user, null jika superadmin.
     */
    private function getUserBusinessUnitId()
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin()) {
            return null;
        }
        $profile = $user->drmsProfile;
        if (!$profile || !$profile->business_unit_id) {
            abort(403, 'Anda tidak memiliki unit bisnis.');
        }
        return $profile->business_unit_id;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $this->getUserBusinessUnitId();

        $query = Voucher::with('businessUnit');

        // Filter Business Unit (kecuali superadmin)
        if ($businessUnitId) {
            $query->where('business_unit_id', $businessUnitId);
        }

        // Filter pencarian (kode)
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where('code', 'LIKE', $search);
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter Tipe
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter Business Unit (khusus superadmin)
        if ($user->isDrmsSuperAdmin() && $request->filled('business_unit_id')) {
            $query->where('business_unit_id', $request->business_unit_id);
        }

        $vouchers = $query->latest()->paginate(20)->appends($request->query());

        // Ambil daftar business unit untuk dropdown (khusus superadmin)
        $businessUnits = [];
        if ($user->isDrmsSuperAdmin()) {
            $businessUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();
        }

        return view('drms.vouchers.index', compact('vouchers', 'businessUnits'));
    }

    public function create()
    {
        $this->getUserBusinessUnitId(); // validasi akses
        return view('drms.vouchers.create');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'code'    => 'required|string|unique:drms_vouchers',
            'nominal' => 'required|numeric|min:0',
            'type'    => 'required|in:grab,gojek,taxi',
            'status'  => 'required|in:available,used',
        ]);

        if ($user->isDrmsSuperAdmin()) {
            $data['business_unit_id'] = $request->business_unit_id ?? null;
        } else {
            $data['business_unit_id'] = $this->getUserBusinessUnitId();
        }

        Voucher::create($data);

        return redirect()->route('drms.vouchers.index')
            ->with('success', 'Voucher berhasil ditambahkan.');
    }

    public function edit(Voucher $voucher)
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin()) {
            $businessUnitId = $this->getUserBusinessUnitId();
            if ($voucher->business_unit_id !== $businessUnitId) {
                abort(403, 'Anda tidak memiliki akses ke voucher ini.');
            }
        }
        return view('drms.vouchers.edit', compact('voucher'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin()) {
            $businessUnitId = $this->getUserBusinessUnitId();
            if ($voucher->business_unit_id !== $businessUnitId) {
                abort(403, 'Anda tidak memiliki akses ke voucher ini.');
            }
        }

        $data = $request->validate([
            'code'    => 'required|string|unique:drms_vouchers,code,' . $voucher->id,
            'nominal' => 'required|numeric|min:0',
            'type'    => 'required|in:grab,gojek,taxi',
            'status'  => 'required|in:available,used',
        ]);

        $voucher->update($data);

        return redirect()->route('drms.vouchers.index')
            ->with('success', 'Voucher berhasil diperbarui.');
    }

    public function destroy(Voucher $voucher)
    {
        $user = Auth::user();
        if (!$user->isDrmsSuperAdmin()) {
            $businessUnitId = $this->getUserBusinessUnitId();
            if ($voucher->business_unit_id !== $businessUnitId) {
                abort(403, 'Anda tidak memiliki akses ke voucher ini.');
            }
        }
        $voucher->delete();
        return redirect()->route('drms.vouchers.index')
            ->with('success', 'Voucher dihapus.');
    }
}