<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\AreaKerja;
use App\Models\StockCtl\Barang;
use App\Models\StockCtl\Stok;
use App\Models\StockCtl\Transaksi;
use App\Models\StockCtl\Permintaan;
use App\Models\StockCtl\LaporanHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    public function index()
    {
        $access = session('stock_ctl_access');

        $areas = $access['is_super']
            ? AreaKerja::with('bisnisUnit')->get()
            : AreaKerja::where('id_bisnis_unit', $access['id_bisnis_unit'])->get();

        $barang = Barang::orderBy('nama_barang')->get();

        // Ambil 10 history terbaru sesuai unit user
        $historyQuery = LaporanHistory::with('user', 'area', 'barang')
            ->orderBy('dicetak_pada', 'desc');

        if (!$access['is_super']) {
            $historyQuery->where(function($q) use ($access) {
                $q->whereHas('area', function($sub) use ($access) {
                    $sub->where('id_bisnis_unit', $access['id_bisnis_unit']);
                })->orWhereNull('id_area');
            });
        }

        $recentHistory = $historyQuery->limit(10)->get();

        return view('stock-ctl.laporan.index', compact('areas', 'barang', 'recentHistory'));
    }

    public function pdf(Request $request)
    {
        $request->validate([
            'jenis'         => 'required|in:stok,mutasi,permintaan',
            'id_area'       => 'nullable|exists:stock_ctl_area_kerja,id_area_kerja',
            'id_barang'     => 'nullable|exists:stock_ctl_barang,id_barang',
            'tanggal_awal'  => 'nullable|date',
            'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal_awal',
        ]);

        $access = session('stock_ctl_access');

        // Validasi area
        if (!$access['is_super'] && $request->id_area) {
            $area = AreaKerja::find($request->id_area);
            if (!$area || $area->id_bisnis_unit != $access['id_bisnis_unit']) {
                abort(403, 'Anda tidak memiliki akses ke area tersebut.');
            }
        }

        // Ambil data sesuai jenis
        $data = [];
        $judul = '';

        switch ($request->jenis) {
            case 'stok':
                $judul = 'Laporan Stok';
                $data = $this->getDataStok($request, $access);
                break;
            case 'mutasi':
                $judul = 'Laporan Mutasi Barang';
                $data = $this->getDataMutasi($request, $access);
                break;
            case 'permintaan':
                $judul = 'Laporan Permintaan';
                $data = $this->getDataPermintaan($request, $access);
                break;
        }

        // Tambahkan info filter
        $data['filter'] = [
            'area'   => $request->id_area ? AreaKerja::find($request->id_area)->nama_area : 'Semua Area',
            'barang' => $request->id_barang ? Barang::find($request->id_barang)->nama_barang : 'Semua Barang',
            'periode' => $request->tanggal_awal && $request->tanggal_akhir
                ? date('d/m/Y', strtotime($request->tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($request->tanggal_akhir))
                : ($request->tanggal_awal ? 'Mulai ' . date('d/m/Y', strtotime($request->tanggal_awal)) : 'Semua Periode'),
        ];
        $data['judul'] = $judul;
        $data['user'] = auth()->user();

        // Generate PDF
        $pdf = Pdf::loadView('stock-ctl.laporan.pdf.' . $request->jenis, $data)
            ->setPaper('a4', 'landscape');

        // Simpan history
        $this->saveHistory($request);

        return $pdf->download('laporan_' . $request->jenis . '_' . date('YmdHis') . '.pdf');
    }

    /**
     * Menyimpan history cetak laporan.
     */
    private function saveHistory($request)
    {
        LaporanHistory::create([
            'id_user'       => auth()->id(),
            'jenis'         => $request->jenis,
            'id_area'       => $request->id_area,
            'id_barang'     => $request->id_barang,
            'tanggal_awal'  => $request->tanggal_awal,
            'tanggal_akhir' => $request->tanggal_akhir,
            'nama_file'     => 'laporan_' . $request->jenis . '_' . date('YmdHis') . '.pdf',
        ]);
    }

    /**
     * Tampilkan riwayat cetak laporan.
     */
    public function history()
    {
        $access = session('stock_ctl_access');
        $query = LaporanHistory::with('user', 'area', 'barang');

        if (!$access['is_super']) {
            $query->where(function($q) use ($access) {
                $q->whereHas('area', function($sub) use ($access) {
                    $sub->where('id_bisnis_unit', $access['id_bisnis_unit']);
                })->orWhereNull('id_area');
            });
        }

        $histories = $query->orderBy('dicetak_pada', 'desc')->paginate(20);
        return view('stock-ctl.laporan.history', compact('histories'));
    }

    // ----- Data Retrieval Methods -----

    private function getDataStok($request, $access)
    {
        $query = Stok::with('barang', 'areaKerja.bisnisUnit');

        if (!$access['is_super']) {
            $query->whereHas('areaKerja', function ($q) use ($access) {
                $q->where('id_bisnis_unit', $access['id_bisnis_unit']);
            });
        }

        if ($request->id_area) {
            $query->where('id_area_kerja', $request->id_area);
        }

        if ($request->id_barang) {
            $query->where('id_barang', $request->id_barang);
        }

        $stok = $query->orderBy('id_area_kerja')->orderBy('id_barang')->get();
        return compact('stok');
    }

    private function getDataMutasi($request, $access)
    {
        $query = Transaksi::with('barang', 'areaAsal', 'areaTujuan', 'user');

        if (!$access['is_super']) {
            $query->where(function ($q) use ($access) {
                $q->whereHas('areaAsal', function ($sub) use ($access) {
                    $sub->where('id_bisnis_unit', $access['id_bisnis_unit']);
                })->orWhereHas('areaTujuan', function ($sub) use ($access) {
                    $sub->where('id_bisnis_unit', $access['id_bisnis_unit']);
                });
            });
        }

        if ($request->id_area) {
            $query->where(function ($q) use ($request) {
                $q->where('id_area_asal', $request->id_area)
                  ->orWhere('id_area_tujuan', $request->id_area);
            });
        }

        if ($request->id_barang) {
            $query->where('id_barang', $request->id_barang);
        }

        if ($request->tanggal_awal) {
            $query->whereDate('tanggal', '>=', $request->tanggal_awal);
        }

        if ($request->tanggal_akhir) {
            $query->whereDate('tanggal', '<=', $request->tanggal_akhir);
        }

        $transaksi = $query->orderBy('tanggal', 'desc')->get();
        return compact('transaksi');
    }

    private function getDataPermintaan($request, $access)
    {
        $query = Permintaan::with(
                'pemohon.profil',        // <-- tambahkan ini
                'barang',
                'areaKerja',
                'approverL1',
                'approverAdmin'
            );

        if (!$access['is_super']) {
            $query->whereExists(function ($q) use ($access) {
                $q->select(DB::raw(1))
                ->from('stock_ctl_user_profil')
                ->whereColumn('stock_ctl_user_profil.id_user', 'stock_ctl_permintaan.id_user_pemohon')
                ->where('stock_ctl_user_profil.id_bisnis_unit', $access['id_bisnis_unit']);
            });
        }

        if ($request->id_area) {
            $query->where('id_area_kerja', $request->id_area);
        }

        if ($request->id_barang) {
            $query->where('id_barang', $request->id_barang);
        }

        if ($request->tanggal_awal) {
            $query->whereDate('tanggal_permintaan', '>=', $request->tanggal_awal);
        }

        if ($request->tanggal_akhir) {
            $query->whereDate('tanggal_permintaan', '<=', $request->tanggal_akhir);
        }

        $permintaan = $query->orderBy('tanggal_permintaan', 'desc')->get();

        return compact('permintaan');
    }
}