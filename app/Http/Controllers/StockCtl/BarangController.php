<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\Barang;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        $query = Barang::query();
        if ($request->filled('search')) {
            $query->where('kode_barang', 'like', "%{$request->search}%")
                  ->orWhere('nama_barang', 'like', "%{$request->search}%");
        }
        $barang = $query->orderBy('kode_barang')->paginate(15);
        return view('stock-ctl.barang.index', compact('barang'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_barang' => 'required|unique:stock_ctl_barang,kode_barang',
            'nama_barang' => 'required',
            'satuan' => 'nullable|string|max:20',
            'harga' => 'nullable|numeric|min:0',
            'deskripsi' => 'nullable|string',
        ]);

        Barang::create($request->all());
        return redirect()->route('stock-ctl.barang.index')
            ->with('success', 'Barang berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);
        $request->validate([
            'kode_barang' => 'required|unique:stock_ctl_barang,kode_barang,' . $id . ',id_barang',
            'nama_barang' => 'required',
            'satuan' => 'nullable|string|max:20',
            'harga' => 'nullable|numeric|min:0',
            'deskripsi' => 'nullable|string',
        ]);

        $barang->update($request->all());
        return redirect()->route('stock-ctl.barang.index')
            ->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $barang = Barang::findOrFail($id);
        if ($barang->stok()->exists() || $barang->transaksi()->exists()) {
            return back()->withErrors('Barang tidak dapat dihapus karena masih memiliki data stok/transaksi.');
        }
        $barang->delete();
        return redirect()->route('stock-ctl.barang.index')
            ->with('success', 'Barang dihapus.');
    }
}