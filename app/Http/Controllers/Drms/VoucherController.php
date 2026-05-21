<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    /**
     * Ambil business_unit_id user dari profil DRMS, dengan fallback untuk superadmin.
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

    public function index()
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin()) {
            $vouchers = Voucher::latest()->get();
        } else {
            $businessUnitId = $this->getUserBusinessUnitId();
            $vouchers = Voucher::where('business_unit_id', $businessUnitId)->latest()->get();
        }
        return view('drms.vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        $this->getUserBusinessUnitId();
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