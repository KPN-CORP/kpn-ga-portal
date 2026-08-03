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
        $user = Auth::user();
        $isSpecialBu = $this->isSpecialBusinessUnitUser();
        $isSuperAdmin = $user->isDrmsSuperAdmin();

        // Daftar business unit untuk pilihan "Business Unit Tujuan" (khusus user KPN Corporation)
        // DAN untuk pilihan "Business Unit" utama voucher (khusus superadmin, karena superadmin
        // tidak terikat ke 1 business unit tertentu sehingga wajib pilih manual).
        $businessUnits = ($isSpecialBu || $isSuperAdmin)
            ? BisnisUnit::orderBy('nama_bisnis_unit')->get()
            : collect();

        // Business Unit milik user sendiri:
        // - Untuk superadmin: dipakai buat PRE-SELECT dropdown di atas (kalau akun superadmin-nya
        //   kebetulan juga terhubung ke 1 BU tertentu), tapi tetap bisa diganti bebas.
        // - Untuk user BU biasa (non-superadmin, non-KPN Corporation): ditampilkan langsung sebagai
        //   info "Business Unit: <nama BU sendiri>" di form, bukan field "Dibebankan ke BU".
        $ownBusinessUnitId = $user->drmsProfile?->business_unit_id ?? null;
        $ownBusinessUnitName = $user->drmsProfile?->businessUnit?->nama_bisnis_unit ?? null;

        return view('drms.vouchers.create', compact(
            'businessUnits', 'isSpecialBu', 'isSuperAdmin', 'ownBusinessUnitId', 'ownBusinessUnitName'
        ));
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
        if ($user->isDrmsSuperAdmin()) {
            $rules['business_unit_id'] = 'required|exists:tb_bisnis_unit,id_bisnis_unit';
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
        $isSuperAdmin = $user->isDrmsSuperAdmin();
        $businessUnits = ($isSpecialBu || $isSuperAdmin)
            ? BisnisUnit::orderBy('nama_bisnis_unit')->get()
            : collect();

        return view('drms.vouchers.edit', compact('voucher', 'businessUnits', 'isSpecialBu', 'isSuperAdmin'));
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
        if ($user->isDrmsSuperAdmin()) {
            $rules['business_unit_id'] = 'nullable|exists:tb_bisnis_unit,id_bisnis_unit';
        }

        $data = $request->validate($rules);

        // business_unit_id hanya boleh diubah oleh superadmin (user BU biasa terkunci ke BU-nya sendiri,
        // sudah dijamin lewat pengecekan akses di atas — tidak boleh edit voucher BU lain sama sekali).
        if ($user->isDrmsSuperAdmin() && $request->filled('business_unit_id')) {
            $data['business_unit_id'] = $request->business_unit_id;
        }

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
        $isSuperAdmin = $user->isDrmsSuperAdmin();
        $isSpecialBu = $this->isSpecialBusinessUnitUser();
        $businessUnits = $isSuperAdmin
            ? BisnisUnit::orderBy('nama_bisnis_unit')->get()
            : collect();

        // Kalau akun superadmin kebetulan juga terhubung ke 1 BU tertentu di profilnya,
        // pre-select itu di dropdown (tetap bisa diganti bebas ke BU lain).
        $ownBusinessUnitId = $user->drmsProfile?->business_unit_id ?? null;

        return view('drms.vouchers.upload', compact('businessUnits', 'ownBusinessUnitId', 'isSuperAdmin', 'isSpecialBu'));
    }

    /**
     * Download template upload voucher (.xlsx). Sekarang cuma 1 format template
     * (kolom kode_voucher mendukung 1 kode atau gabungan "kode1 & kode2" langsung).
     */
    public function downloadTemplate()
    {
        $this->getUserBusinessUnitId(); // validasi akses
        return Excel::download(new VoucherTemplateExport(), 'template_voucher.xlsx');
    }

    /**
     * Proses upload voucher dari file Excel/CSV.
     * Voucher otomatis mengambil business unit user yang input, kecuali:
     * - Superadmin bisa override per baris lewat kolom "Business Unit" di file
     *   (atau pakai BU default yang dipilih di form kalau kolomnya kosong).
     * - User BU khusus (KPN Corporation) bisa isi kolom "Dibebankan ke BU" per baris.
     */
    public function upload(Request $request)
    {
        $user = Auth::user();
        $isSpecialBu = $this->isSpecialBusinessUnitUser();

        $rules = [
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

        $import = new VoucherImport($businessUnitId, $defaultExpiredAt, $user->isDrmsSuperAdmin(), $isSpecialBu);

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