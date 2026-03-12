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
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $access = session('stock_ctl_access');

        $query = Permintaan::with('barang', 'pemohon', 'approver');

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

        // Filter berdasarkan request
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('barang', function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }

        if ($request->filled('dari')) {
            $query->whereDate('tanggal_permintaan', '>=', $request->dari);
        }

        if ($request->filled('sampai')) {
            $query->whereDate('tanggal_permintaan', '<=', $request->sampai);
        }

        $permintaan = $query->orderBy('tanggal_permintaan', 'desc')->paginate(15)->withQueryString();

        // Ambil semua barang untuk dropdown modal create
        $barang = Barang::all();

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
            'id_barang'  => 'required|exists:stock_ctl_barang,id_barang',
            'jumlah'     => 'required|numeric|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $user = Auth::user();
        $profil = UserProfil::where('id_user', $user->id)->first();
        if (!$profil || !$profil->id_area_kerja) {
            return back()->withErrors('Profil area kerja belum diatur. Silakan hubungi admin.');
        }

        // Validasi: area harus sesuai dengan unit user
        $area = AreaKerja::find($profil->id_area_kerja);
        if (!$area || $area->id_bisnis_unit != $profil->id_bisnis_unit) {
            return back()->withErrors('Profil area kerja tidak sesuai dengan unit bisnis Anda. Silakan hubungi admin.');
        }

        $permintaan = Permintaan::create([
            'id_user_pemohon' => $user->id,
            'id_barang'       => $request->id_barang,
            'jumlah'          => $request->jumlah,
            'keterangan'      => $request->keterangan,
            'status'          => Permintaan::STATUS_PENDING_L1,
            'id_area_kerja'   => $profil->id_area_kerja,
        ]);

        // Kirim notifikasi ke atasan (L1)
        if ($profil->id_approver) {
            $approver = User::find($profil->id_approver);
            if ($approver) {
                $approver->notify(new PermintaanBaruL1($permintaan));
            }
        }

        return redirect()->route('stock-ctl.permintaan.index')
            ->with('success', 'Permintaan berhasil diajukan, menunggu approval atasan.');
    }

    /**
     * Menampilkan detail permintaan (digunakan oleh modal).
     */
    public function show($id)
    {
        $permintaan = Permintaan::with('barang', 'pemohon', 'approver', 'areaKerja')
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

        // Untuk admin, cek unit pemohon
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