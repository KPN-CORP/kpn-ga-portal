<?php

namespace App\Http\Controllers\Drms;

use App\Http\Controllers\Controller;
use App\Models\Drms\Voucher;
use App\Models\BisnisUnit;
use App\Models\Drms\DrmsUserProfile;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
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
     * Area yang dipegang admin yang login (drms_admin_areas).
     * [] = superadmin atau admin lintas area (tidak dibatasi).
     */
    private function getUserAreas(): array
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin() || !$user->drmsProfile) {
            return [];
        }
        return $user->drmsProfile->adminAreaList();
    }

    /**
     * Daftar area yang dikenal untuk sebuah BU (diambil dari area di drms_user_profiles).
     */
    private function areasForBu($businessUnitId): Collection
    {
        return DrmsUserProfile::where('business_unit_id', $businessUnitId)
            ->whereNotNull('area')->where('area', '!=', '')
            ->distinct()->orderBy('area')->pluck('area')
            ->map(fn ($a) => trim($a))->unique()->values();
    }

    /**
     * Pilihan area untuk dropdown form: admin ber-area hanya area-areanya sendiri
     * (1 area = terkunci), admin lintas area semua area BU-nya, superadmin: peta
     * [BU => area-area] untuk JS.
     */
    private function areaOptions(): array
    {
        $user = Auth::user();
        if ($user->isDrmsSuperAdmin()) {
            $map = DrmsUserProfile::whereNotNull('area')->where('area', '!=', '')
                ->select('business_unit_id', 'area')->distinct()->get()
                ->groupBy('business_unit_id')
                ->map(fn ($rows) => $rows->pluck('area')->map(fn ($a) => trim($a))->unique()->sort()->values());
            return ['areas' => collect(), 'areasByBu' => $map, 'lockedArea' => null];
        }
        $mine = $this->getUserAreas();
        $areas = !empty($mine) ? collect($mine) : $this->areasForBu($this->getUserBusinessUnitId());
        return [
            'areas'      => $areas,
            'areasByBu'  => collect(),
            'lockedArea' => count($mine) === 1 ? $mine[0] : null,
        ];
    }

    /**
     * Tentukan area voucher yang akan disimpan.
     * Admin 1 area: dikunci ke area itu (input diabaikan).
     * Admin multi-area: wajib salah satu area miliknya.
     * Admin lintas area / superadmin: wajib area valid untuk BU tsb.
     */
    private function resolveArea(?string $input, $businessUnitId): string
    {
        $mine = $this->getUserAreas();
        if (count($mine) === 1) {
            return $mine[0];
        }
        $input = trim((string) $input);
        $allowed = !empty($mine) ? collect($mine) : $this->areasForBu($businessUnitId);
        $match = $allowed->first(fn ($a) => strcasecmp($a, $input) === 0);
        if ($input === '' || !$match) {
            throw ValidationException::withMessages(['area' => 'Area tidak valid atau bukan area yang Anda pegang.']);
        }
        return $match;
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

        $query = Voucher::with([
            'businessUnit',
            'inputBusinessUnit',
            'usedByRequests.requester', // siapa yang pakai (voucher tambahan / data baru)
            'request.requester',        // siapa yang pakai (voucher utama / data lama)
        ]);

        // Filter Business Unit (kecuali superadmin).
        // KECUALI: admin tetap boleh IKUT LIHAT (read-only) voucher milik BU lain,
        // KALAU voucher itu dipakai oleh request dari user yang berasal dari BU-nya
        // sendiri. Ini buat kasus request user-nya di-forward ke BU lain (karena BU
        // asal gak punya mobil) — vouchernya kepunyaan BU yang nanganin, tapi
        // admin BU asal tetap perlu lihat karena itu request anak buahnya.
        // Hak edit/hapus TETAP dikunci ke BU pemilik asli (lihat edit/update/destroy).
        // Cakupan BU + AREA. Superadmin: semua. Admin lintas area (area profil kosong): semua area
        // di BU-nya. Admin ber-area: hanya voucher area-nya sendiri.
        // Pengecualian read-only (request di-forward) tetap ada, tapi hanya untuk request
        // pemohon yang BU-nya sama (dan area-nya sama jika admin ber-area).
        $adminAreas = $this->getUserAreas();
        if ($businessUnitId) {
            $query->where(function ($q) use ($businessUnitId, $adminAreas) {
                $q->where(function ($own) use ($businessUnitId, $adminAreas) {
                      $own->where('business_unit_id', $businessUnitId);
                      if (!empty($adminAreas)) {
                          $own->whereIn('area', $adminAreas);
                      }
                  })
                  ->orWhereHas('usedByRequests.requester.drmsProfile', function ($sq) use ($businessUnitId, $adminAreas) {
                      $sq->where('business_unit_id', $businessUnitId);
                      if (!empty($adminAreas)) {
                          $sq->whereIn('area', $adminAreas);
                      }
                  })
                  ->orWhereHas('request.requester.drmsProfile', function ($sq) use ($businessUnitId, $adminAreas) {
                      $sq->where('business_unit_id', $businessUnitId);
                      if (!empty($adminAreas)) {
                          $sq->whereIn('area', $adminAreas);
                      }
                  });
            });
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

        // Filter Area (superadmin, admin lintas area, atau admin multi-area memilih salah satu
        // area miliknya; admin 1 area sudah terkunci)
        if ($request->filled('area') && (empty($adminAreas) || in_array($request->area, $adminAreas, true))) {
            $query->where('area', $request->area);
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

        $areaOptions = $this->areaOptions();
        $areaFilterOptions = $user->isDrmsSuperAdmin()
            ? $areaOptions['areasByBu']->flatten()->unique()->sort()->values()
            : $areaOptions['areas'];
        $isAreaLocked = count($adminAreas) === 1;

        return view('drms.vouchers.index', compact('vouchers', 'businessUnits', 'isSpecialBu', 'month', 'businessUnitId', 'areaFilterOptions', 'isAreaLocked'));
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

        $areaOptions = $this->areaOptions();

        return view('drms.vouchers.create', array_merge(compact(
            'businessUnits', 'isSpecialBu', 'isSuperAdmin', 'ownBusinessUnitId', 'ownBusinessUnitName'
        ), $areaOptions));
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

        $rules['area'] = 'nullable|string|max:255';
        $data = $request->validate($rules);

        if ($user->isDrmsSuperAdmin()) {
            $data['business_unit_id'] = $request->business_unit_id ?? null;
        } else {
            $data['business_unit_id'] = $this->getUserBusinessUnitId();
        }

        // Area: terkunci untuk admin ber-area, wajib dipilih untuk admin lintas area / superadmin.
        $data['area'] = $this->resolveArea($request->area, $data['business_unit_id']);

        // input_business_unit_id hanya diisi untuk user KPN Corporation
        $data['input_business_unit_id'] = $isSpecialBu ? ($request->input_business_unit_id ?? null) : null;

        Voucher::create($data);

        return redirect()->route('drms.vouchers.index')
            ->with('success', 'Voucher berhasil ditambahkan.');
    }

    public function edit(Voucher $voucher)
    {
        $user = Auth::user();
        if (!$voucher->canBeManagedBy($user)) {
            abort(403, 'Anda tidak memiliki akses ke voucher ini.');
        }

        $isSpecialBu = $this->isSpecialBusinessUnitUser();
        $isSuperAdmin = $user->isDrmsSuperAdmin();
        $businessUnits = ($isSpecialBu || $isSuperAdmin)
            ? BisnisUnit::orderBy('nama_bisnis_unit')->get()
            : collect();

        $areaOptions = $this->areaOptions();

        return view('drms.vouchers.edit', array_merge(
            compact('voucher', 'businessUnits', 'isSpecialBu', 'isSuperAdmin'), $areaOptions
        ));
    }

    public function update(Request $request, Voucher $voucher)
    {
        $user = Auth::user();
        if (!$voucher->canBeManagedBy($user)) {
            abort(403, 'Anda tidak memiliki akses ke voucher ini.');
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

        $rules['area'] = 'nullable|string|max:255';
        $data = $request->validate($rules);

        // Area: admin ber-area tidak bisa memindahkan voucher ke area lain.
        // Admin lintas area / superadmin boleh, tapi harus area valid di BU tujuan.
        $targetBu = ($user->isDrmsSuperAdmin() && $request->filled('business_unit_id'))
            ? $request->business_unit_id
            : $voucher->business_unit_id;
        $newArea = count($this->getUserAreas()) === 1 ? $voucher->area : $this->resolveArea($request->area, $targetBu);
        if (strcasecmp((string) $newArea, (string) $voucher->area) !== 0
            && ($voucher->usedByRequests()->exists() || $voucher->request()->exists())) {
            return back()->withErrors('Area voucher yang sudah pernah dipakai request tidak bisa diubah.')->withInput();
        }
        $data['area'] = $newArea;

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
        if (!$voucher->canBeManagedBy($user)) {
            abort(403, 'Anda tidak memiliki akses ke voucher ini.');
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

        $areaOptions = $this->areaOptions();

        return view('drms.vouchers.upload', array_merge(
            compact('businessUnits', 'ownBusinessUnitId', 'isSuperAdmin', 'isSpecialBu'), $areaOptions
        ));
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

        // Area untuk seluruh voucher dalam file ini (admin ber-area: otomatis area-nya sendiri).
        $request->validate(['area' => 'nullable|string|max:255']);
        $uploadArea = $this->resolveArea($request->area, $businessUnitId);

        // Tanggal expired default: dipakai untuk baris yang tidak mengisi kolom
        // expired_at sendiri di file. VoucherImport yang menentukan prioritasnya
        // (kolom per baris jika diisi, kalau tidak pakai default ini).
        $defaultExpiredAt = $request->filled('expired_at') ? $request->expired_at : null;

        $import = new VoucherImport($businessUnitId, $defaultExpiredAt, $user->isDrmsSuperAdmin(), $isSpecialBu, $uploadArea);

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