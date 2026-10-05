<?php
namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplies\SuppliesBarang;
use App\Models\Supplies\SuppliesStok;
use App\Models\Supplies\SuppliesTransaksi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SuppliesStokController extends Controller
{
    public function __construct() { $this->middleware('supplies.access:admin'); }

    public function index(Request $request)
    {
        $query = SuppliesStok::with('barang');
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('barang', fn($q) => $q->where('nama_barang', 'like', "%{$search}%")->orWhere('kode_barang', 'like', "%{$search}%"));
        }
        $stok = $query->orderBy('id_barang')->paginate(15);
        return view('supplies.stok.index', compact('stok'));
    }

    public function createMasuk()
    {
        $barang = SuppliesBarang::all();
        return view('supplies.stok.masuk', compact('barang'));
    }

    public function storeMasuk(Request $request)
    {
        $request->validate([
            'id_barang' => 'required|exists:supplies_barang,id',
            'jumlah' => 'required|numeric|min:0.01',
        ]);
        DB::transaction(function () use ($request) {
            $stok = SuppliesStok::firstOrCreate(
                ['id_barang' => $request->id_barang],
                ['jumlah' => 0]
            );
            $stok->increment('jumlah', $request->jumlah);

            SuppliesTransaksi::create([
                'jenis' => 'masuk',
                'id_barang' => $request->id_barang,
                'jumlah' => $request->jumlah,
                'no_ref' => $request->no_ref,
                'keterangan' => $request->keterangan,
                'id_user' => Auth::id(),
            ]);
        });
        return redirect()->route('supplies.stok.index')->with('success', 'Stok masuk dicatat.');
    }
}