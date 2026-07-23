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
     * Nama business unit yang mendapat fitur tambahan "Business Unit Tujuan"
     * (input_business_unit_id) pada voucher.
     */
    private const SPECIAL_BU_NAME = 'KPN Corporation';

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

    /**
     * Cek apakah user yang login berasal dari business unit khusus (KPN Corporation).
     * Fitur "input_business_unit_id" hanya ditampilkan untuk user ini.
     */
    private function isSpecialBusinessUnitUser(): bool
    {
        $user = Auth::user();
        $profile = $user->drmsProfile ?? null;
        $namaBu = $profile->businessUnit->nama_bisnis_unit ?? null;

        return $namaBu !== null && strcasecmp(trim($namaBu), self::SPECIAL_BU_NAME) === 0;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $businessUnitId = $this->getUserBusinessUnitId();
        $isSpecialBu = $this->isSpecialBusinessUnitUser();

        $query = Voucher::with(['businessUnit', 'inputBusinessUnit']);

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

        // Filter Business Unit Tujuan / input_business_unit_id (khusus user KPN Corporation)
        if ($isSpecialBu && $request->filled('input_business_unit_id')) {
            $query->where('input_business_unit_id', $request->input_business_unit_id);
        }

        $vouchers = $query->latest()->paginate(20)->appends($request->query());

        // Ambil daftar business unit untuk dropdown (khusus superadmin, dan untuk filter/pilihan input_business_unit_id)
        $businessUnits = [];
        if ($user->isDrmsSuperAdmin() || $isSpecialBu) {
            $businessUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();
        }

        return view('drms.vouchers.index', compact('vouchers', 'businessUnits', 'isSpecialBu'));
    }

    public function create()
    {
        $this->getUserBusinessUnitId(); // validasi akses
        $isSpecialBu = $this->isSpecialBusinessUnitUser();

        // Daftar business unit untuk pilihan "Business Unit Tujuan" (khusus user KPN Corporation)
        $businessUnits = $isSpecialBu
            ? BisnisUnit::orderBy('nama_bisnis_unit')->get()
            : collect();

        return view('drms.vouchers.create', compact('businessUnits', 'isSpecialBu'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isSpecialBu = $this->isSpecialBusinessUnitUser();

        $rules = [
            'code'    => 'required|string|unique:drms_vouchers',
            'nominal' => 'required|numeric|min:0',
            'type'    => 'required|in:grab,gojek,taxi',
            'status'  => 'required|in:available,used',
        ];

        if ($isSpecialBu) {
            $rules['input_business_unit_id'] = 'nullable|exists:tb_bisnis_unit,id_bisnis_unit';
        }

        $data = $request->validate($rules);

        if ($user->isDrmsSuperAdmin()) {
            $data['business_unit_id'] = $request->business_unit_id ?? null;
        } else {
            $data['business_unit_id'] = $this->getUserBusinessUnitId();
        }

        // input_business_unit_id hanya diisi untuk user KPN Corporation
        $data['input_business_unit_id'] = $isSpecialBu ? ($request->input_business_unit_id ?? null) : null;

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

        $isSpecialBu = $this->isSpecialBusinessUnitUser();
        $businessUnits = $isSpecialBu
            ? BisnisUnit::orderBy('nama_bisnis_unit')->get()
            : collect();

        return view('drms.vouchers.edit', compact('voucher', 'businessUnits', 'isSpecialBu'));
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

        $isSpecialBu = $this->isSpecialBusinessUnitUser();

        $rules = [
            'code'    => 'required|string|unique:drms_vouchers,code,' . $voucher->id,
            'nominal' => 'required|numeric|min:0',
            'type'    => 'required|in:grab,gojek,taxi',
            'status'  => 'required|in:available,used',
        ];

        if ($isSpecialBu) {
            $rules['input_business_unit_id'] = 'nullable|exists:tb_bisnis_unit,id_bisnis_unit';
        }

        $data = $request->validate($rules);

        // input_business_unit_id hanya diubah untuk user KPN Corporation, selain itu dibiarkan seperti semula
        if ($isSpecialBu) {
            $data['input_business_unit_id'] = $request->input_business_unit_id ?? null;
        }

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
