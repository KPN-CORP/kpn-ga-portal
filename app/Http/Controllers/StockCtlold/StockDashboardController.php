<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\Stok;
use App\Models\StockCtl\Permintaan;
use App\Models\StockCtl\Transaksi;
use App\Models\StockCtl\AreaKerja;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $access = session('stock_ctl_access');

        if ($access['is_super']) {
            $stok = Stok::with('barang', 'areaKerja.bisnisUnit')->get();
            $permintaanPending = Permintaan::where('status', 'pending')->count();
            $transaksiHariIni = Transaksi::whereDate('tanggal', today())->count();
        } else {
            // Stok: hanya dari unit user
            $stok = Stok::with('barang')
                ->whereHas('areaKerja', fn($q) => $q->where('id_bisnis_unit', $access['id_bisnis_unit']))
                ->get();

            // Permintaan pending: hanya dari unit yang sama dengan user
            $permintaanPending = Permintaan::where('status', 'pending')
                ->whereExists(function ($q) use ($access) {
                    $q->select(DB::raw(1))
                      ->from('stock_ctl_user_profil')
                      ->whereColumn('stock_ctl_user_profil.id_user', 'stock_ctl_permintaan.id_user_pemohon')
                      ->where('stock_ctl_user_profil.id_bisnis_unit', $access['id_bisnis_unit']);
                })
                ->count();

            // Transaksi hari ini: yang melibatkan area dari unit user
            $transaksiHariIni = Transaksi::where(function($q) use ($access) {
                    $q->whereHas('areaAsal', fn($sub) => $sub->where('id_bisnis_unit', $access['id_bisnis_unit']))
                      ->orWhereHas('areaTujuan', fn($sub) => $sub->where('id_bisnis_unit', $access['id_bisnis_unit']));
                })
                ->whereDate('tanggal', today())
                ->count();
        }

        // Ambil 5 stok terbaru untuk ditampilkan di dashboard
        $stokTerbaru = $stok->take(5);

        return view('stock-ctl.dashboard', compact('stokTerbaru', 'permintaanPending', 'transaksiHariIni', 'access'));
    }
}