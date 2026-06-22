<?php

namespace App\Http\Controllers\Supplies;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Supplies\SuppliesBarang;
use App\Models\Supplies\SuppliesPermintaan;
use App\Models\Supplies\SuppliesStok;
use App\Models\BisnisUnit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // <-- TAMBAHKAN INI

class SuppliesPermintaanController extends Controller
{
    public function __construct()
    {
        $this->middleware('supplies.access:user');
    }

    public function index(Request $request)
    {
        $query = SuppliesPermintaan::with('barang', 'bisnisUnit')->where('id_user_pemohon', Auth::id());
        if ($request->filled('status')) $query->where('status', $request->status);
        $permintaan = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('supplies.permintaan.index', compact('permintaan'));
    }

    public function create()
    {
        $barang = SuppliesBarang::all();
        $bisnisUnits = BisnisUnit::all();
        return view('supplies.permintaan.create', compact('barang', 'bisnisUnits'));
    }

public function store(Request $request)
{
    $request->validate([
        'items' => 'required|array|min:1|max:5',
        'items.*.id_barang' => 'required|exists:supplies_barang,id',
        'items.*.id_bisnis_unit' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
        'items.*.jumlah' => 'required|numeric|min:0.01',
        'items.*.keterangan' => 'nullable|string',
    ]);

    $user = Auth::id();
    $successCount = 0;
    $failedItems = [];

    foreach ($request->items as $item) {
        $stok = SuppliesStok::where('id_barang', $item['id_barang'])
            ->where('id_bisnis_unit', $item['id_bisnis_unit'])
            ->first();
        $barang = SuppliesBarang::find($item['id_barang']);

        // Cek stok
        if (!$stok || $stok->jumlah < $item['jumlah']) {
            $tersedia = $stok ? $stok->jumlah : 0;
            $failedItems[] = "{$barang->nama_barang} (butuh {$item['jumlah']}, tersedia {$tersedia} {$barang->satuan})";
            continue; // skip item ini, lanjut ke item berikutnya
        }

        // Simpan permintaan
        SuppliesPermintaan::create([
            'id_user_pemohon' => $user,
            'id_barang' => $item['id_barang'],
            'id_bisnis_unit' => $item['id_bisnis_unit'],
            'jumlah' => $item['jumlah'],
            'keterangan' => $item['keterangan'] ?? '',
            'status' => 'pending',
        ]);
        $successCount++;
    }

    // Buat pesan
    if ($successCount > 0 && empty($failedItems)) {
        return redirect()->route('supplies.permintaan.index')
            ->with('success', "{$successCount} permintaan berhasil diajukan.");
    } elseif ($successCount > 0 && !empty($failedItems)) {
        return redirect()->route('supplies.permintaan.index')
            ->with('warning', "{$successCount} permintaan berhasil, namun beberapa gagal: " . implode(', ', $failedItems));
    } else {
        return back()->withErrors("Tidak ada permintaan yang bisa diajukan. " . implode(', ', $failedItems))->withInput();
    }
}

    public function show($id)
    {
        $permintaan = SuppliesPermintaan::with('barang', 'bisnisUnit', 'approver')->findOrFail($id);
        if ($permintaan->id_user_pemohon != Auth::id()) abort(403);
        return view('supplies.permintaan.show', compact('permintaan'));
    }
}