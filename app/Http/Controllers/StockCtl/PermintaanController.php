<?php

namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\Barang;
use App\Models\StockCtl\Permintaan;
use App\Models\StockCtl\UserProfil;
use App\Models\StockCtl\AreaKerja;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Notifications\PermintaanBaruL1;

class PermintaanController extends Controller
{
    /**
     * Menampilkan daftar permintaan milik user yang login, dengan filter.
     * Default status = 'disetujui' jika tidak ada parameter status.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $access = session('stock_ctl_access');

        // Query utama dengan join ke users untuk mendapatkan nama approver
        $query = Permintaan::select(
                'stock_ctl_permintaan.*',
                'l1.name as approver_l1_name',
                'admin.name as approver_admin_name'
            )
            ->leftJoin('users as l1', 'l1.id', '=', 'stock_ctl_permintaan.approved_l1_by')
            ->leftJoin('users as admin', 'admin.id', '=', 'stock_ctl_permintaan.approved_admin_by')
            ->with('barang', 'pemohon.profil', 'substitusi');

        // Menu Permintaan = permintaan MILIK SENDIRI saja (semua role, termasuk admin & superadmin).
        // Untuk melihat permintaan orang lain, admin memakai menu Approval Admin.
        $query->where('stock_ctl_permintaan.id_user_pemohon', $user->id);

        // ========== DEFAULT STATUS = 'disetujui' ==========
        // Jika tidak ada parameter status, set default ke 'disetujui'
        $status = $request->get('status', 'disetujui');
        if ($status) {
            $query->where('stock_ctl_permintaan.status', $status);
        }
        // ==================================================

        // Filter berdasarkan request (search, tanggal)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('barang', function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }
        if ($request->filled('dari')) {
            $query->whereDate('stock_ctl_permintaan.tanggal_permintaan', '>=', $request->dari);
        }
        if ($request->filled('sampai')) {
            $query->whereDate('stock_ctl_permintaan.tanggal_permintaan', '<=', $request->sampai);
        }

        $permintaan = $query->orderBy('stock_ctl_permintaan.tanggal_permintaan', 'desc')
                            ->paginate(15)
                            ->withQueryString();

        // Tambahkan nama atasan (approver L1) untuk setiap permintaan
        foreach ($permintaan as $item) {
            $profil = UserProfil::where('id_user', $item->id_user_pemohon)->first();
            if ($profil && $profil->id_approver) {
                $approver = User::find($profil->id_approver);
                $item->approver_name = $approver ? $approver->name : '-';
            } else {
                $item->approver_name = '-';
            }
        }

        // ========== FILTER BARANG BERDASARKAN AREA KERJA USER ==========
        $profil = UserProfil::where('id_user', $user->id)->first();
        $userAreaId = $profil->id_area_kerja ?? null;

        if ($access['is_super']) {
            $barang = Barang::all();
        } else {
            if ($userAreaId) {
                $barang = Barang::whereHas('stok', function ($q) use ($userAreaId) {
                    $q->where('id_area_kerja', $userAreaId);
                })->get();
            } else {
                $barang = collect();
            }
        }
        // =============================================================

        return view('stock-ctl.permintaan.index', compact('permintaan', 'barang'));
    }

    /**
     * Menampilkan form pengajuan permintaan (tidak digunakan karena modal).
     */
    public function create()
    {
        $barang = Barang::all();
        return view('stock-ctl.permintaan.create', compact('barang'));
    }

    /**
     * Menyimpan permintaan baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'items' => 'required|array|min:1|max:5',
            'items.*.id_barang' => 'required|exists:stock_ctl_barang,id_barang',
            'items.*.jumlah' => 'required|numeric|min:0.01',
            'items.*.keterangan' => 'required|string',
        ]);

        $user = Auth::user();
        $access = session('stock_ctl_access');

        // ========== VALIDASI HAK AKSES BARANG BERDASARKAN AREA KERJA ==========
        if (!$access['is_super']) {
            $profil = UserProfil::where('id_user', $user->id)->first();
            $userAreaId = $profil->id_area_kerja ?? null;
            if (!$userAreaId) {
                return back()->withErrors(['msg' => 'Area kerja belum ditentukan. Hubungi admin.']);
            }

            $allowedBarangIds = Barang::whereHas('stok', function ($q) use ($userAreaId) {
                $q->where('id_area_kerja', $userAreaId);
            })->pluck('id_barang')->toArray();

            foreach ($request->items as $item) {
                if (!in_array($item['id_barang'], $allowedBarangIds)) {
                    return back()->withErrors(['msg' => 'Barang tidak tersedia di area kerja Anda.']);
                }
            }
        }
        // =============================================================

        $profil = UserProfil::where('id_user', $user->id)->first();
        if (!$profil || !$profil->id_area_kerja) {
            return back()->withErrors('Profil area kerja belum diatur. Silakan hubungi admin.');
        }

        $area = AreaKerja::find($profil->id_area_kerja);
        if (!$area || $area->id_bisnis_unit != $profil->id_bisnis_unit) {
            return back()->withErrors('Profil area kerja tidak sesuai dengan unit bisnis Anda. Silakan hubungi admin.');
        }

        // ========== ANTI PENGAJUAN GANDA ==========
        // 1) Token sekali pakai per pembukaan form: klik ganda / request ganda dengan token sama ditolak.
        $tokenKey = null;
        if ($request->filled('submission_token')) {
            $tokenKey = 'permintaan_submit:' . $user->id . ':' . $request->submission_token;
            if (!Cache::add($tokenKey, 1, now()->addMinutes(10))) {
                return redirect()->route('stock-ctl.permintaan.index', ['status' => 'pending_l1'])
                    ->with('success', 'Permintaan Anda sudah diajukan sebelumnya.');
            }
        }

        try {
            $permintaanIds = DB::transaction(function () use ($request, $user, $profil) {
                $ids = [];
                foreach ($request->items as $item) {
                    // 2) Pengaman tambahan: item identik dari user yang sama dalam 60 detik terakhir
                    //    (masih pending L1) dianggap duplikat dan dilewati.
                    $duplikat = Permintaan::where('id_user_pemohon', $user->id)
                        ->where('status', Permintaan::STATUS_PENDING_L1)
                        ->where('id_barang', $item['id_barang'])
                        ->where('jumlah', $item['jumlah'])
                        ->where('keterangan', $item['keterangan'] ?? null)
                        ->whereRaw('tanggal_permintaan >= (NOW() - INTERVAL 60 SECOND)')
                        ->lockForUpdate()
                        ->exists();
                    if ($duplikat) {
                        continue;
                    }

                    $permintaan = Permintaan::create([
                        'id_user_pemohon' => $user->id,
                        'id_barang'       => $item['id_barang'],
                        'jumlah'          => $item['jumlah'],
                        'keterangan'      => $item['keterangan'] ?? null,
                        'status'          => Permintaan::STATUS_PENDING_L1,
                        'id_area_kerja'   => $profil->id_area_kerja,
                    ]);
                    $ids[] = $permintaan->id_permintaan;
                }
                return $ids;
            });
        } catch (\Throwable $e) {
            if ($tokenKey) {
                Cache::forget($tokenKey);   // gagal -> user boleh mencoba lagi dengan form yang sama
            }
            throw $e;
        }

        if (empty($permintaanIds)) {
            return redirect()->route('stock-ctl.permintaan.index', ['status' => 'pending_l1'])
                ->with('success', 'Permintaan yang sama baru saja diajukan, tidak dibuat ulang.');
        }
        // ==========================================

        // Kirim notifikasi ke atasan (L1)
        if ($profil->id_approver) {
            $approver = User::find($profil->id_approver);
            if ($approver) {
                foreach ($permintaanIds as $id) {
                    $approver->notify(new PermintaanBaruL1(Permintaan::find($id)));
                }
            }
        }

        return redirect()->route('stock-ctl.permintaan.index')
            ->with('success', count($permintaanIds) . ' permintaan berhasil diajukan, menunggu approval atasan.');
    }

    /**
     * Menampilkan detail permintaan (digunakan oleh modal).
     */
    public function show($id)
    {
        $permintaan = Permintaan::with('barang', 'pemohon', 'approverL1', 'approverAdmin', 'areaKerja')
            ->findOrFail($id);
        $this->authorizeView($permintaan);
        return view('stock-ctl.permintaan.show', compact('permintaan'));
    }

    /**
     * Pemohon membatalkan permintaannya sendiri selama masih Pending L1.
     * Update bersifat atomik: hanya berhasil bila status MASIH pending_l1 dan milik user ini,
     * sehingga aman jika atasan menyetujui/menolak di saat yang sama.
     */
    public function batalkan(Request $request, $id)
    {
        $user = Auth::user();

        $affected = Permintaan::where('id_permintaan', $id)
            ->where('id_user_pemohon', $user->id)
            ->where('status', Permintaan::STATUS_PENDING_L1)
            ->update([
                'status'       => Permintaan::STATUS_CANCELLED,
                'rejected_at'  => now(),               // dipakai sebagai waktu pembatalan
                'alasan_tolak' => 'Dibatalkan oleh pemohon',
            ]);

        if ($affected === 0) {
            $ada = Permintaan::where('id_permintaan', $id)->where('id_user_pemohon', $user->id)->exists();
            abort_unless($ada, 403, 'Anda hanya dapat membatalkan permintaan milik sendiri.');

            return back()->withErrors(['msg' => 'Permintaan tidak dapat dibatalkan karena sudah diproses atasan.']);
        }

        return redirect()
            ->route('stock-ctl.permintaan.index', ['status' => 'pending_l1'])
            ->with('success', 'Permintaan ' . (optional(Permintaan::find($id))->no_tampil ?: 'ATK-SC-' . $id) . ' berhasil dibatalkan.');
    }

    /**
     * Menampilkan history permintaan (riwayat semua status).
     */
    public function history()
    {
        $user = Auth::user();
        $permintaan = Permintaan::with('barang')
            ->where('id_user_pemohon', $user->id)
            ->orderBy('tanggal_permintaan', 'desc')
            ->paginate(15);
        return view('stock-ctl.permintaan.history', compact('permintaan'));
    }

    /**
     * Otorisasi akses ke detail permintaan.
     * - Pemilik permintaan: boleh.
     * - Atasan L1 pemohon: boleh (link dari notifikasi approval).
     * - Superadmin: boleh semua.
     * - Admin: boleh untuk permintaan di unit bisnisnya.
     * Daftar menu Permintaan tetap hanya menampilkan permintaan milik sendiri (lihat index()).
     */
    private function authorizeView($permintaan)
    {
        $user = Auth::user();
        $access = session('stock_ctl_access');

        if ($permintaan->id_user_pemohon == $user->id) return;

        if (!empty($access['is_super'])) return;

        $profilPemohon = DB::table('stock_ctl_user_profil')
            ->where('id_user', $permintaan->id_user_pemohon)
            ->first();

        if (!empty($access['is_admin']) && $profilPemohon
            && $profilPemohon->id_bisnis_unit == ($access['id_bisnis_unit'] ?? null)) {
            return;
        }

        if ($profilPemohon && $profilPemohon->id_approver && $profilPemohon->id_approver == $user->id) return;

        abort(403, 'Anda tidak berhak mengakses permintaan ini.');
    }
}