<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\AntarUnitRequest;
use App\Models\StockCtl\Barang;
use App\Models\BisnisUnit;
use Illuminate\Support\Facades\Auth;

class AntarUnitRequestController extends Controller
{
    public function index(Request $request)
    {
        $access = session('stock_ctl_access');
        $query = AntarUnitRequest::with('barang', 'unitAsal', 'unitTujuan', 'pemohon');

        // Filter by unit asal
        if ($request->filled('unit_asal')) {
            $query->where('id_bisnis_unit_asal', $request->unit_asal);
        }

        // Filter by unit tujuan
        if ($request->filled('unit_tujuan')) {
            $query->where('id_bisnis_unit_tujuan', $request->unit_tujuan);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Batasan akses: jika bukan super, hanya tampilkan unit asal sendiri
        if (!$access['is_super']) {
            $query->where('id_bisnis_unit_asal', $access['id_bisnis_unit']);
        }

        // Jumlah data per halaman
        $perPage = $request->input('per_page', 15);
        if (!in_array($perPage, [15, 25, 50, 100])) {
            $perPage = 15;
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Ambil semua unit untuk dropdown filter
        $units = BisnisUnit::all();

        return view('stock-ctl.antar-unit.index', compact('requests', 'units', 'perPage'));
    }

    public function create()
    {
        $access = session('stock_ctl_access');
        if (!$access['is_admin'] && !$access['is_super']) {
            abort(403, 'Hanya admin yang dapat mengajukan permintaan antar unit.');
        }
        $barang = Barang::all();
        $units = BisnisUnit::all();
        $userUnitId = $access['id_bisnis_unit'];
        return view('stock-ctl.antar-unit.create', compact('barang', 'units', 'userUnitId'));
    }

    public function store(Request $request)
    {
        $access = session('stock_ctl_access');
        if (!$access['is_admin'] && !$access['is_super']) {
            abort(403);
        }
        $request->validate([
            'id_barang'   => 'required|exists:stock_ctl_barang,id_barang',
            'jumlah'      => 'required|numeric|min:0.01',
            'id_bisnis_unit_tujuan' => 'required|exists:tb_bisnis_unit,id_bisnis_unit|different:id_bisnis_unit_asal',
            'keterangan'  => 'nullable|string',
        ]);
        $userUnitId = $access['id_bisnis_unit'];
        AntarUnitRequest::create([
            'id_user_pemohon'        => Auth::id(),
            'id_barang'              => $request->id_barang,
            'jumlah'                 => $request->jumlah,
            'id_bisnis_unit_asal'    => $userUnitId,
            'id_bisnis_unit_tujuan'  => $request->id_bisnis_unit_tujuan,
            'keterangan'             => $request->keterangan,
            'status'                 => AntarUnitRequest::STATUS_PENDING,
        ]);
        return redirect()->route('stock-ctl.antar-unit.index')
            ->with('success', 'Permintaan antar unit berhasil diajukan.');
    }

    public function show($id)
    {
        $request = AntarUnitRequest::with('barang', 'unitAsal', 'unitTujuan', 'pemohon', 'approver')->findOrFail($id);
        return view('stock-ctl.antar-unit.show', compact('request'));
    }
}