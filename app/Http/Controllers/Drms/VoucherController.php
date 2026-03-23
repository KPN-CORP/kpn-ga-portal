<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoucherController extends Controller
{
    public function index()
    {
        $vouchers = Voucher::where('business_unit_id', Auth::user()->business_unit_id)
            ->latest()
            ->get();
        return view('drms.vouchers.index', compact('vouchers'));
    }

    public function create()
    {
        return view('drms.vouchers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:drms_vouchers',
            'nominal' => 'required|numeric|min:0',
            'type' => 'required|in:grab,gojek,taxi',
            'status' => 'required|in:available,used',
        ]);

        $data['business_unit_id'] = Auth::user()->business_unit_id;

        Voucher::create($data);

        return redirect()->route('drms.vouchers.index')
            ->with('success', 'Voucher berhasil ditambahkan.');
    }

    public function edit(Voucher $voucher)
    {
        if ($voucher->business_unit_id !== Auth::user()->business_unit_id) {
            abort(403);
        }
        return view('drms.vouchers.edit', compact('voucher'));
    }

    public function update(Request $request, Voucher $voucher)
    {
        if ($voucher->business_unit_id !== Auth::user()->business_unit_id) {
            abort(403);
        }

        $data = $request->validate([
            'code' => 'required|string|unique:drms_vouchers,code,' . $voucher->id,
            'nominal' => 'required|numeric|min:0',
            'type' => 'required|in:grab,gojek,taxi',
            'status' => 'required|in:available,used',
        ]);

        $voucher->update($data);

        return redirect()->route('drms.vouchers.index')
            ->with('success', 'Voucher berhasil diperbarui.');
    }

    public function destroy(Voucher $voucher)
    {
        if ($voucher->business_unit_id !== Auth::user()->business_unit_id) {
            abort(403);
        }
        $voucher->delete();
        return redirect()->route('drms.vouchers.index')
            ->with('success', 'Voucher dihapus.');
    }
}