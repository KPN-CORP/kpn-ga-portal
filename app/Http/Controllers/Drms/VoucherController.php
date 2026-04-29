<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    /**
     * Ambil business_unit_id user dari profil DRMS, dengan fallback aman.
     */
    private function getUserBusinessUnitId()
    {
        $profile = Auth::user()->drmsProfile;
        if (!$profile || !$profile->business_unit_id) {
            abort(403, 'Anda tidak memiliki unit bisnis.');
        }
        return $profile->business_unit_id;
    }

    public function index()
    {
        $businessUnitId = $this->getUserBusinessUnitId();
        $vouchers = Voucher::where('business_unit_id', $businessUnitId)
            ->latest()
            ->get();
        return view('drms.vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        // Pastikan user punya business unit (biar tidak error di view jika diperlukan)
        $this->getUserBusinessUnitId();
        return view('drms.vouchers.create');
    }

    public function store(Request $request)
    {
        $businessUnitId = $this->getUserBusinessUnitId();

        $data = $request->validate([
            'code'    => 'required|string|unique:drms_vouchers',
            'nominal' => 'required|numeric|min:0',
            'type'    => 'required|in:grab,gojek,taxi',
            'status'  => 'required|in:available,used',
        ]);

        $data['business_unit_id'] = $businessUnitId;

        Voucher::create($data);

        return redirect()->route('drms.vouchers.index')
            ->with('success', 'Voucher berhasil ditambahkan.');
    }

    public function edit(Voucher $voucher)
    {
        $businessUnitId = $this->getUserBusinessUnitId();
        if ($voucher->business_unit_id !== $businessUnitId) {
            abort(403, 'Anda tidak memiliki akses ke voucher ini.');
        }
        return view('drms.vouchers.edit', compact('voucher'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $businessUnitId = $this->getUserBusinessUnitId();
        if ($voucher->business_unit_id !== $businessUnitId) {
            abort(403, 'Anda tidak memiliki akses ke voucher ini.');
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
        $businessUnitId = $this->getUserBusinessUnitId();
        if ($voucher->business_unit_id !== $businessUnitId) {
            abort(403, 'Anda tidak memiliki akses ke voucher ini.');
        }
        $voucher->delete();
        return redirect()->route('drms.vouchers.index')
            ->with('success', 'Voucher dihapus.');
    }
}