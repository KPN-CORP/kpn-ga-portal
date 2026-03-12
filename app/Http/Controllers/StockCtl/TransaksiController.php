<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\Transaksi;
use App\Models\StockCtl\Barang;
use App\Models\StockCtl\AreaKerja;
use App\Models\StockCtl\Stok;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransaksiController extends Controller
{
    public function index(Request $request)
    {
        $access = session('stock_ctl_access');
        $query = Transaksi::with('barang', 'areaAsal', 'areaTujuan', 'user');

        if (!$access['is_super']) {
            $query->where(function($q) use ($access) {
                $q->whereHas('areaAsal', function($sub) use ($access) {
                    $sub->where('id_bisnis_unit', $access['id_bisnis_unit']);
                })->orWhereHas('areaTujuan', function($sub) use ($access) {
                    $sub->where('id_bisnis_unit', $access['id_bisnis_unit']);
                });
            });
        }

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }
        if ($request->filled('tanggal_awal')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_awal);
        }
        if ($request->filled('tanggal_akhir')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_akhir);
        }

        $transaksi = $query->orderBy('tanggal', 'desc')->paginate(15);
        $jenisList = ['masuk', 'keluar', 'transfer', 'opname'];

        return view('stock-ctl.transaksi.index', compact('transaksi', 'jenisList'));
    }

    public function createMasuk()
    {
        $access = session('stock_ctl_access');
        $barang = Barang::all();
        $areas = $access['is_super'] 
            ? AreaKerja::with('bisnisUnit')->get() 
            : AreaKerja::where('id_bisnis_unit', $access['id_bisnis_unit'])->get();

        return view('stock-ctl.transaksi.masuk', compact('barang', 'areas'));
    }

    public function storeMasuk(Request $request)
    {
        $request->validate([
            'id_area_tujuan' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'id_barang'      => 'required|exists:stock_ctl_barang,id_barang',
            'jumlah'         => 'required|numeric|min:1',
            'no_ref'         => 'nullable|string',
            'keterangan'     => 'nullable|string',
        ]);

        $access = session('stock_ctl_access');
        if (!$access['is_super']) {
            $area = AreaKerja::find($request->id_area_tujuan);
            if (!$area || $area->id_bisnis_unit != $access['id_bisnis_unit']) {
                abort(403);
            }
        }

        DB::transaction(function () use ($request) {
            Stok::updateOrCreate(
                ['id_barang' => $request->id_barang, 'id_area_kerja' => $request->id_area_tujuan],
                ['jumlah' => DB::raw('jumlah + ' . $request->jumlah)]
            );

            Transaksi::create([
                'jenis'          => 'masuk',
                'id_barang'      => $request->id_barang,
                'jumlah'         => $request->jumlah,
                'id_area_tujuan' => $request->id_area_tujuan,
                'no_ref'         => $request->no_ref,
                'keterangan'     => $request->keterangan,
                'id_user'        => Auth::id(),
            ]);
        });

        return redirect()->route('stock-ctl.stok.index')->with('success', 'Barang masuk berhasil dicatat.');
    }

    public function createKeluar()
    {
        $access = session('stock_ctl_access');
        $barang = Barang::all();
        $areas = $access['is_super'] 
            ? AreaKerja::with('bisnisUnit')->get() 
            : AreaKerja::where('id_bisnis_unit', $access['id_bisnis_unit'])->get();

        return view('stock-ctl.transaksi.keluar', compact('barang', 'areas'));
    }

    public function storeKeluar(Request $request)
    {
        $request->validate([
            'id_area_asal' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'id_barang'    => 'required|exists:stock_ctl_barang,id_barang',
            'jumlah'       => 'required|numeric|min:1',
            'no_ref'       => 'nullable|string',
            'keterangan'   => 'nullable|string',
        ]);

        $access = session('stock_ctl_access');
        if (!$access['is_super']) {
            $area = AreaKerja::find($request->id_area_asal);
            if (!$area || $area->id_bisnis_unit != $access['id_bisnis_unit']) {
                abort(403);
            }
        }

        DB::transaction(function () use ($request) {
            $stok = Stok::where('id_barang', $request->id_barang)
                ->where('id_area_kerja', $request->id_area_asal)
                ->first();

            if (!$stok || $stok->jumlah < $request->jumlah) {
                throw new \Exception('Stok tidak mencukupi.');
            }

            $stok->decrement('jumlah', $request->jumlah);

            Transaksi::create([
                'jenis'        => 'keluar',
                'id_barang'    => $request->id_barang,
                'jumlah'       => $request->jumlah,
                'id_area_asal' => $request->id_area_asal,
                'no_ref'       => $request->no_ref,
                'keterangan'   => $request->keterangan,
                'id_user'      => Auth::id(),
            ]);
        });

        return redirect()->route('stock-ctl.stok.index')->with('success', 'Barang keluar berhasil dicatat.');
    }

    public function createTransfer()
    {
        $access = session('stock_ctl_access');
        $barang = Barang::all();
        // Hanya tampilkan area dari unit user (kecuali superadmin)
        $areas = $access['is_super'] 
            ? AreaKerja::with('bisnisUnit')->get() 
            : AreaKerja::where('id_bisnis_unit', $access['id_bisnis_unit'])->get();
        return view('stock-ctl.transaksi.transfer', compact('barang', 'areas'));
    }

    public function storeTransfer(Request $request)
    {
        $request->validate([
            'id_area_asal'   => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'id_area_tujuan' => 'required|exists:stock_ctl_area_kerja,id_area_kerja|different:id_area_asal',
            'id_barang'      => 'required|exists:stock_ctl_barang,id_barang',
            'jumlah'         => 'required|numeric|min:1',
            'keterangan'     => 'nullable|string',
        ]);

        $access = session('stock_ctl_access');
        if (!$access['is_super']) {
            $areaAsal = AreaKerja::find($request->id_area_asal);
            $areaTujuan = AreaKerja::find($request->id_area_tujuan);
            if (!$areaAsal || $areaAsal->id_bisnis_unit != $access['id_bisnis_unit']) {
                abort(403, 'Area asal tidak sesuai dengan unit Anda.');
            }
            // Validasi area tujuan harus satu unit
            if (!$areaTujuan || $areaTujuan->id_bisnis_unit != $access['id_bisnis_unit']) {
                abort(403, 'Area tujuan harus berada dalam unit yang sama.');
            }
        }

        DB::transaction(function () use ($request) {
            $stokAsal = Stok::where('id_barang', $request->id_barang)
                ->where('id_area_kerja', $request->id_area_asal)
                ->first();

            if (!$stokAsal || $stokAsal->jumlah < $request->jumlah) {
                throw new \Exception('Stok di area asal tidak mencukupi.');
            }

            $stokAsal->decrement('jumlah', $request->jumlah);

            Stok::updateOrCreate(
                ['id_barang' => $request->id_barang, 'id_area_kerja' => $request->id_area_tujuan],
                ['jumlah' => DB::raw('jumlah + ' . $request->jumlah)]
            );

            Transaksi::create([
                'jenis'          => 'transfer',
                'id_barang'      => $request->id_barang,
                'jumlah'         => $request->jumlah,
                'id_area_asal'   => $request->id_area_asal,
                'id_area_tujuan' => $request->id_area_tujuan,
                'keterangan'     => $request->keterangan,
                'id_user'        => Auth::id(),
            ]);
        });

        return redirect()->route('stock-ctl.stok.index')->with('success', 'Transfer berhasil.');
    }
}