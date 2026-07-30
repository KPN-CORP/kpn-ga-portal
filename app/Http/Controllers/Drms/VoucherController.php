<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Voucher;
use App\Models\BisnisUnit;
use App\Imports\VoucherImport;
use App\Exports\VoucherTemplateExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

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

        // Filter Bulan (default: bulan sekarang). Pilih "Semua Bulan" (month=all) untuk menonaktifkan filter ini.
        // Filter berdasarkan tanggal EXPIRED voucher, bukan tanggal dibuat.
        $month = $request->get('month', now()->format('Y-m'));
        if ($month !== 'all' && preg_match('/^\d{4}-\d{2}$/', $month)) {
            [$year, $monthNum] = explode('-', $month);
            $query->whereYear('expired_at', $year)->whereMonth('expired_at', $monthNum);
        }

        $vouchers = $query->latest()->paginate(20)->appends($request->query());

        // Ambil daftar business unit untuk dropdown (khusus superadmin, dan untuk filter/pilihan input_business_unit_id)
        $businessUnits = [];
        if ($user->isDrmsSuperAdmin() || $isSpecialBu) {
            $businessUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();
        }

        return view('drms.vouchers.index', compact('vouchers', 'businessUnits', 'isSpecialBu', 'month'));
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
            'code'       => 'required|string|unique:drms_vouchers',
            'nominal'    => 'required|numeric|min:0',
            'type'       => 'required|in:grab,gojek,taxi',
            'status'     => 'required|in:available,used',
            'expired_at' => 'nullable|date',
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
            'code'       => 'required|string|unique:drms_vouchers,code,' . $voucher->id,
            'nominal'    => 'required|numeric|min:0',
            'type'       => 'required|in:grab,gojek,taxi',
            'status'     => 'required|in:available,used',
            'expired_at' => 'nullable|date',
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

    /**
     * Halaman upload voucher massal (bulk import).
     */
    public function uploadForm()
    {
        $this->getUserBusinessUnitId(); // validasi akses
        $user = Auth::user();
        $businessUnits = $user->isDrmsSuperAdmin()
            ? BisnisUnit::orderBy('nama_bisnis_unit')->get()
            : collect();

        return view('drms.vouchers.upload', compact('businessUnits'));
    }

    /**
     * Download template upload voucher (.xlsx).
     * Tipe 'single'  = 1 voucher per baris.
     * Tipe 'double'  = 2 voucher per baris.
     */
    public function downloadTemplate($type)
    {
        $this->getUserBusinessUnitId(); // validasi akses

        $type = $type === 'double' ? 'double' : 'single';
        $filename = $type === 'double'
            ? 'template_voucher_2_per_baris.xlsx'
            : 'template_voucher_1_per_baris.xlsx';

        return Excel::download(new VoucherTemplateExport($type), $filename);
    }

    /**
     * Proses upload voucher dari file Excel/CSV (1 atau 2 voucher per baris).
     * Voucher otomatis mengambil business unit user yang input (kecuali superadmin memilih BU).
     */
    public function upload(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'format'     => 'required|in:single,double',
            'file'       => 'required|file|mimes:xlsx,xls,csv|max:5120',
            'expired_at' => 'nullable|date',
        ];
        if ($user->isDrmsSuperAdmin()) {
            $rules['business_unit_id'] = 'required|exists:tb_bisnis_unit,id_bisnis_unit';
        }
        $request->validate($rules, [], [
            'business_unit_id' => 'Business Unit',
        ]);

        $businessUnitId = $user->isDrmsSuperAdmin()
            ? $request->business_unit_id
            : $this->getUserBusinessUnitId();

        // Tanggal expired default: dipakai untuk baris yang tidak mengisi kolom
        // expired_at sendiri di file. VoucherImport yang menentukan prioritasnya
        // (kolom per baris jika diisi, kalau tidak pakai default ini).
        $defaultExpiredAt = $request->filled('expired_at') ? $request->expired_at : null;

        $import = new VoucherImport($request->format, $businessUnitId, $defaultExpiredAt);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            return back()->with('error', 'File tidak sesuai format template. Pastikan header kolom tidak diubah.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses file: ' . $e->getMessage());
        }

        $message = "{$import->created} voucher berhasil diupload.";
        if ($import->skipped) {
            $message .= " {$import->skipped} baris dilewati (lihat rincian di bawah).";
        }

        return redirect()->route('drms.vouchers.index')
            ->with($import->created > 0 ? 'success' : 'error', $message)
            ->with('upload_errors', $import->errors);
    }
}