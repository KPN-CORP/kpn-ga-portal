<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplies\SuppliesBarang;

class SuppliesBarangController extends Controller
{
    public function __construct()
    {
        $this->middleware('supplies.access:admin');
    }

    public function index(Request $request)
    {
        $query = SuppliesBarang::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('kode_barang', 'like', "%{$search}%")
                  ->orWhere('nama_barang', 'like', "%{$search}%");
            });
        }
        $barang = $query->orderBy('kode_barang')->paginate(15);
        return view('supplies.barang.index', compact('barang'));
    }

    public function create()
    {
        return view('supplies.barang.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode_barang' => 'required|unique:supplies_barang,kode_barang',
            'nama_barang' => 'required',
            'satuan' => 'nullable|string|max:20',
            'lokasi_rak' => 'nullable|string|max:100',
            'stok_minimum' => 'nullable|numeric|min:0',
        ]);

        SuppliesBarang::create($request->all());
        return redirect()->route('supplies.barang.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $barang = SuppliesBarang::findOrFail($id);
        return view('supplies.barang.edit', compact('barang'));
    }

    public function update(Request $request, $id)
    {
        $barang = SuppliesBarang::findOrFail($id);
        $request->validate([
            'kode_barang' => 'required|unique:supplies_barang,kode_barang,'.$id,
            'nama_barang' => 'required',
        ]);
        $barang->update($request->all());
        return redirect()->route('supplies.barang.index')->with('success', 'Barang diperbarui.');
    }

    public function destroy($id)
    {
        $barang = SuppliesBarang::findOrFail($id);
        if ($barang->stok()->exists()) {
            return back()->withErrors('Barang tidak bisa dihapus karena memiliki stok.');
        }
        $barang->delete();
        return redirect()->route('supplies.barang.index')->with('success', 'Barang dihapus.');
    }
}