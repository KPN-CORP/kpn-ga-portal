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
use Illuminate\Support\Facades\DB;
use App\Notifications\PermintaanBaruL1;

class PermintaanController extends Controller
{
    /**
     * Menampilkan daftar permintaan user dengan filter.
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
            ->with('barang', 'pemohon.profil');

        // Filter berdasarkan role
        if ($access['is_super']) {
            // superadmin – semua data
        } elseif ($access['is_admin']) {
            // admin – lihat permintaan dari unit yang sama atau miliknya sendiri
            $query->where(function($q) use ($user, $access) {
                $q->whereExists(function ($sub) use ($access) {
                    $sub->select(DB::raw(1))
                        ->from('stock_ctl_user_profil')
                        ->whereColumn('stock_ctl_user_profil.id_user', 'stock_ctl_permintaan.id_user_pemohon')
                        ->where('stock_ctl_user_profil.id_bisnis_unit', $access['id_bisnis_unit']);
                })->orWhere('id_user_pemohon', $user->id);
            });
        } else {
            // user biasa – hanya permintaan sendiri
            $query->where('id_user_pemohon', $user->id);
        }

        // ========== DEFAULT STATUS = 'disetujui' ==========
        // Jika tidak ada parameter status, set default ke 'disetujui'
        $status = $request->get('status', 'disetujui');
        if ($status) {
            $query->where('stock_ctl_permintaan.status', $status);
        }
        // ==================================================

        // Filter berdasarkan request (search, pemohon, tanggal)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('barang', function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }
        if ($request->filled('pemohon')) {
            $query->whereHas('pemohon', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->pemohon . '%');
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

        $permintaanIds = [];
        foreach ($request->items as $item) {
            $permintaan = Permintaan::create([
                'id_user_pemohon'    => $user->id,
                'id_barang'          => $item['id_barang'],
                'jumlah'             => $item['jumlah'],
                'keterangan'         => $item['keterangan'] ?? null,
                'status'             => Permintaan::STATUS_PENDING_L1,
                'id_area_kerja'      => $profil->id_area_kerja,
            ]);
            $permintaanIds[] = $permintaan->id_permintaan;
        }

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
     */
    private function authorizeView($permintaan)
    {
        $access = session('stock_ctl_access');
        $user = Auth::user();

        if ($access['is_super']) return;

        if ($access['is_admin']) {
            $unitPemohon = DB::table('stock_ctl_user_profil')
                ->where('id_user', $permintaan->id_user_pemohon)
                ->value('id_bisnis_unit');
            if ($unitPemohon == $access['id_bisnis_unit']) return;
        }

        if ($permintaan->id_user_pemohon == $user->id) return;

        abort(403, 'Anda tidak berhak mengakses permintaan ini.');
    }
}