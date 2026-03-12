<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\Opname;
use App\Models\StockCtl\DetailOpname;
use App\Models\StockCtl\Stok;
use App\Models\StockCtl\Barang;
use App\Models\StockCtl\AreaKerja;
use App\Models\StockCtl\Transaksi;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OpnameController extends Controller
{
    public function index()
    {
        $access = session('stock_ctl_access');
        $query = Opname::with('areaKerja.bisnisUnit', 'user');

        if (!$access['is_super']) {
            $query->whereHas('areaKerja', function($q) use ($access) {
                $q->where('id_bisnis_unit', $access['id_bisnis_unit']);
            });
        }

        $opname = $query->orderBy('tanggal_opname', 'desc')->paginate(15);

        $areas = $access['is_super'] 
            ? AreaKerja::with('bisnisUnit')->get() 
            : AreaKerja::where('id_bisnis_unit', $access['id_bisnis_unit'])->get();

        return view('stock-ctl.opname.index', compact('opname', 'areas'));
    }

    public function create()
    {
        $access = session('stock_ctl_access');
        $areas = $access['is_super'] 
            ? AreaKerja::with('bisnisUnit')->get() 
            : AreaKerja::where('id_bisnis_unit', $access['id_bisnis_unit'])->get();
        return view('stock-ctl.opname.create', compact('areas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_area_kerja'  => 'required|exists:stock_ctl_area_kerja,id_area_kerja',
            'tanggal_opname' => 'required|date',
        ]);

        $access = session('stock_ctl_access');
        if (!$access['is_super']) {
            $area = AreaKerja::find($request->id_area_kerja);
            if (!$area || $area->id_bisnis_unit != $access['id_bisnis_unit']) {
                abort(403);
            }
        }

        $opname = Opname::create([
            'id_area_kerja'  => $request->id_area_kerja,
            'tanggal_opname' => $request->tanggal_opname,
            'id_user'        => Auth::id(),
            'status'         => 'draft',
        ]);

        return redirect()->route('stock-ctl.opname.edit', $opname->id_opname);
    }

    public function edit($id)
    {
        $opname = Opname::with('areaKerja')->findOrFail($id);
        $access = session('stock_ctl_access');
        if (!$access['is_super'] && $opname->areaKerja->id_bisnis_unit != $access['id_bisnis_unit']) {
            abort(403);
        }

        $stok = Stok::with('barang')
            ->whereHas('areaKerja', function($q) use ($opname) {
                $q->where('id_bisnis_unit', $opname->areaKerja->id_bisnis_unit);
            })
            ->get();

        $details = DetailOpname::where('id_opname', $id)->get()->keyBy('id_barang');

        return view('stock-ctl.opname.edit', compact('opname', 'stok', 'details'));
    }

    public function update(Request $request, $id)
    {
        $opname = Opname::with('areaKerja')->findOrFail($id);
        $access = session('stock_ctl_access');
        if (!$access['is_super'] && $opname->areaKerja->id_bisnis_unit != $access['id_bisnis_unit']) {
            abort(403);
        }

        $request->validate([
            'items'                     => 'required|array',
            'items.*.id_barang'         => 'required|exists:stock_ctl_barang,id_barang',
            'items.*.stok_fisik'        => 'required|numeric|min:0',
            'items.*.keterangan'        => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->items as $item) {
                if (!isset($item['id_barang'])) continue;

                $stokSistem = Stok::where('id_barang', $item['id_barang'])
                    ->where('id_area_kerja', $opname->id_area_kerja)
                    ->value('jumlah') ?? 0;

                DetailOpname::updateOrCreate(
                    ['id_opname' => $id, 'id_barang' => $item['id_barang']],
                    [
                        'stok_sistem' => $stokSistem,
                        'stok_fisik'  => $item['stok_fisik'],
                        'keterangan'  => $item['keterangan'] ?? null,
                    ]
                );
            }

            if ($request->has('selesai')) {
                $details = DetailOpname::where('id_opname', $id)->get();
                foreach ($details as $detail) {
                    $selisih = $detail->stok_fisik - $detail->stok_sistem;
                    if ($selisih != 0) {
                        Stok::where('id_barang', $detail->id_barang)
                            ->where('id_area_kerja', $opname->id_area_kerja)
                            ->update(['jumlah' => $detail->stok_fisik]);

                        Transaksi::create([
                            'jenis'          => 'opname',
                            'id_barang'      => $detail->id_barang,
                            'jumlah'         => abs($selisih),
                            'id_area_asal'   => $selisih < 0 ? $opname->id_area_kerja : null,
                            'id_area_tujuan' => $selisih > 0 ? $opname->id_area_kerja : null,
                            'keterangan'     => 'Penyesuaian opname #' . $id . ($detail->keterangan ? ': ' . $detail->keterangan : ''),
                            'id_user'        => Auth::id(),
                            'no_ref'         => 'OP-' . $id,
                        ]);
                    }
                }
                $opname->update(['status' => 'selesai']);
            }

            DB::commit();
            return redirect()->route('stock-ctl.opname.index')->with('success', 'Opname berhasil diproses.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $opname = Opname::with('areaKerja.bisnisUnit', 'user', 'details.barang')->findOrFail($id);
        $access = session('stock_ctl_access');
        if (!$access['is_super'] && $opname->areaKerja->id_bisnis_unit != $access['id_bisnis_unit']) {
            abort(403);
        }
        return view('stock-ctl.opname.show', compact('opname'));
    }
}