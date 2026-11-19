<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\Stok;
use App\Models\StockCtl\Barang;
use App\Models\StockCtl\AreaKerja;
use App\Models\StockCtl\Transaksi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StokController extends Controller
{
    public function index(Request $request)
    {
        $access = session('stock_ctl_access');

        // Query utama tanpa join manual (memanfaatkan relasi Eloquent)
        $query = Stok::with('barang', 'areaKerja.bisnisUnit');

        // Filter area
        $areaId = $request->input('id_area_kerja') ?? $request->input('id_area');

        if (!$access['is_super']) {
            $query->whereHas('areaKerja', function($q) use ($access) {
                $q->where('id_bisnis_unit', $access['id_bisnis_unit']);
            });
            if ($areaId) {
                $query->where('id_area_kerja', $areaId);
            }
        } else {
            if ($areaId) {
                $query->where('id_area_kerja', $areaId);
            }
        }

        // Filter pencarian (menggunakan whereHas agar tetap memanfaatkan relasi)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('barang', function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }

        // 🔽 URUTKAN BERDASARKAN NAMA BARANG (menggunakan subquery)
        // Ambil nama tabel dari model secara dinamis untuk menghindari hardcode
        $stokTable = (new Stok)->getTable();        // 'stock_ctl_stok'
        $barangTable = (new Barang)->getTable();    // 'stock_ctl_barang'

        $query->orderBy(
            Barang::select('nama_barang')
                ->whereColumn("{$stokTable}.id_barang", '=', "{$barangTable}.id_barang")
                ->limit(1)
        );

        $stok = $query->paginate(15)->withQueryString();

        // Dropdown area
        $areas = $access['is_super'] 
            ? AreaKerja::with('bisnisUnit')->orderBy('nama_area')->get() 
            : AreaKerja::where('id_bisnis_unit', $access['id_bisnis_unit'])->orderBy('nama_area')->get();

        return view('stock-ctl.stok.index', compact('stok', 'areas'));
    }

    public function createAwal()
    {
        $access = session('stock_ctl_access');
        $barang = Barang::all();
        $areas = $access['is_super'] 
            ? AreaKerja::with('bisnisUnit')->get() 
            : AreaKerja::where('id_bisnis_unit', $access['id_bisnis_unit'])->get();

        return view('stock-ctl.stok.awal', compact('barang', 'areas'));
    }

    public function storeAwal(Request $request)
    {
        $request->validate([
            'id_area_kerja' => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'id_barang'     => 'required|exists:stock_ctl_barang,id_barang',
            'jumlah'        => 'required|numeric|min:0',
            'stok_minimum'  => 'nullable|numeric|min:0',
        ]);

        $access = session('stock_ctl_access');
        if (!$access['is_super']) {
            $area = AreaKerja::find($request->id_area_kerja);
            if (!$area || $area->id_bisnis_unit != $access['id_bisnis_unit']) {
                abort(403, 'Anda tidak memiliki akses ke area ini.');
            }
        }

        DB::beginTransaction();
        try {
            Stok::updateOrCreate(
                ['id_barang' => $request->id_barang, 'id_area_kerja' => $request->id_area_kerja],
                ['jumlah' => $request->jumlah, 'stok_minimum' => $request->stok_minimum ?? 0]
            );

            Transaksi::create([
                'jenis'          => 'masuk',
                'id_barang'      => $request->id_barang,
                'jumlah'         => $request->jumlah,
                'id_area_tujuan' => $request->id_area_kerja,
                'keterangan'     => 'Stok awal',
                'id_user'        => Auth::id(),
                'no_ref'         => 'AWAL-' . now()->format('YmdHis'),
            ]);

            DB::commit();
            return redirect()->route('stock-ctl.stok.index')->with('success', 'Stok awal berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}