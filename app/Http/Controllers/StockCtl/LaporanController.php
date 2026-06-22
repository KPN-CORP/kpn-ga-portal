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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class LaporanController extends Controller
{
    public function index()
    {
        $access = session('stock_ctl_access');

        $areas = $access['is_super']
            ? AreaKerja::with('bisnisUnit')->get()
            : AreaKerja::where('id_bisnis_unit', $access['id_bisnis_unit'])->with('bisnisUnit')->get();

        $barang = Barang::orderBy('nama_barang')->get();

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

    /**
     * Ekspor laporan ke CSV (Excel)
     */
    public function excel(Request $request)
    {
        // Tingkatkan memory limit
        ini_set('memory_limit', '512M');
        set_time_limit(300);

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

        switch ($request->jenis) {
            case 'stok':
                $data = $this->getDataStok($request, $access);
                $headers = ['Area', 'Kode Barang', 'Nama Barang', 'Satuan', 'Stok', 'Harga (Rp)', 'Nilai (Rp)', 'Stok Minimum', 'Status', 'Update Terakhir'];
                $rows = $this->formatStokRows($data['stok']);
                $filename = 'laporan_stok_' . date('YmdHis') . '.csv';
                break;
            case 'mutasi':
                $data = $this->getDataMutasi($request, $access);
                $headers = ['Tanggal', 'Jenis', 'Barang', 'Jumlah', 'Satuan', 'Harga (Rp)', 'Nilai (Rp)', 'Area Asal', 'Area Tujuan', 'Keterangan', 'User'];
                $rows = $this->formatMutasiRows($data['transaksi']);
                $filename = 'laporan_mutasi_' . date('YmdHis') . '.csv';
                break;
            case 'permintaan':
                $data = $this->getDataPermintaan($request, $access);
                $headers = [
                    'No. Permintaan', 'Tanggal', 'Pemohon', 'Unit', 'Area',
                    'Barang', 'Jumlah', 'Satuan', 'Keterangan',
                    'Harga (Rp)', 'Nilai (Rp)', 'Status',
                    'Approver L1', 'Approved L1 At',
                    'Approver Admin', 'Approved Admin At',
                    'Rejected By', 'Rejected At', 'Alasan Penolakan'
                ];
                $rows = $this->formatPermintaanRows($data['permintaan']);
                $filename = 'laporan_permintaan_' . date('YmdHis') . '.csv';
                break;
            default:
                abort(400);
        }

        // Simpan history
        $this->saveHistory($request);

        return Response::streamDownload(function () use ($headers, $rows, $request) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // BOM UTF-8
            fputcsv($output, $headers);

            $totalNilai = 0;
            foreach ($rows as $row) {
                fputcsv($output, $row);
                if ($request->jenis == 'stok') {
                    $totalNilai += floatval(str_replace(',', '', $row[6]));
                } elseif ($request->jenis == 'mutasi') {
                    $totalNilai += floatval(str_replace(',', '', $row[6]));
                } elseif ($request->jenis == 'permintaan') {
                    $totalNilai += floatval(str_replace(',', '', $row[10])); // Nilai di indeks 10
                }
            }

            // Tambah baris total
            if ($request->jenis == 'stok') {
                fputcsv($output, ['', '', '', '', '', 'TOTAL NILAI:', number_format($totalNilai, 2), '', '', '']);
            } elseif ($request->jenis == 'mutasi') {
                fputcsv($output, ['', '', '', '', '', '', 'TOTAL NILAI:', number_format($totalNilai, 2), '', '', '']);
            } elseif ($request->jenis == 'permintaan') {
                $totalRow = array_fill(0, 19, '');
                $totalRow[9]  = 'TOTAL NILAI:';
                $totalRow[10] = number_format($totalNilai, 2);
                fputcsv($output, $totalRow);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function saveHistory($request)
    {
        $access = session('stock_ctl_access');
        $idBisnisUnit = $access['id_bisnis_unit'] ?? null;

        LaporanHistory::create([
            'id_user'        => auth()->id(),
            'id_bisnis_unit' => $idBisnisUnit,
            'jenis'          => $request->jenis,
            'id_area'        => $request->id_area,
            'id_barang'      => $request->id_barang,
            'tanggal_awal'   => $request->tanggal_awal,
            'tanggal_akhir'  => $request->tanggal_akhir,
            'nama_file'      => 'laporan_' . $request->jenis . '_' . date('YmdHis') . '.csv',
        ]);
    }

    public function history()
    {
        $access = session('stock_ctl_access');
        $query = LaporanHistory::with('user', 'area.bisnisUnit', 'barang');

        if (!$access['is_super']) {
            $query->where('id_bisnis_unit', $access['id_bisnis_unit']);
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
        $query = Transaksi::with('barang', 'areaAsal.bisnisUnit', 'areaTujuan.bisnisUnit', 'user');

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
                'pemohon.profil',
                'barang',
                'areaKerja.bisnisUnit',
                'approverL1',
                'approverAdmin',
                'rejector'
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

    // ----- Formatting Helpers -----

    private function formatStokRows($stok)
    {
        return $stok->map(function ($item) {
            $harga = $item->barang->harga ?? 0;
            $nilai = $item->jumlah * $harga;
            return [
                ($item->areaKerja->nama_area ?? '-') . ' (' . ($item->areaKerja->bisnisUnit->nama_bisnis_unit ?? '-') . ')',
                $item->barang->kode_barang ?? '-',
                $item->barang->nama_barang ?? '-',
                $item->barang->satuan ?? '-',
                number_format($item->jumlah, 2),
                number_format($harga, 2),
                number_format($nilai, 2),
                number_format($item->stok_minimum, 2),
                $item->jumlah <= $item->stok_minimum ? 'Menipis' : 'Aman',
                $item->last_update ? \Carbon\Carbon::parse($item->last_update)->format('d M Y H:i') : '-',
            ];
        })->toArray();
    }

    private function formatMutasiRows($transaksi)
    {
        return $transaksi->map(function ($item) {
            $harga = $item->barang->harga ?? 0;
            $nilai = $item->jumlah * $harga;
            return [
                \Carbon\Carbon::parse($item->tanggal)->format('d M Y H:i'),
                ucfirst($item->jenis),
                $item->barang->nama_barang ?? '-',
                number_format($item->jumlah, 2),
                $item->barang->satuan ?? '-',
                number_format($harga, 2),
                number_format($nilai, 2),
                $item->areaAsal ? ($item->areaAsal->nama_area . ' (' . ($item->areaAsal->bisnisUnit->nama_bisnis_unit ?? '-') . ')') : '-',
                $item->areaTujuan ? ($item->areaTujuan->nama_area . ' (' . ($item->areaTujuan->bisnisUnit->nama_bisnis_unit ?? '-') . ')') : '-',
                $item->keterangan ?? '-',
                $item->user->name ?? '-',
            ];
        })->toArray();
    }

    private function formatPermintaanRows($permintaan)
    {
        return $permintaan->map(function ($item) {
            $unit = optional($item->pemohon->profil)->unit ?? '';
            $unitName = $unit ? explode(' (', $unit)[0] : '-';
            $harga = $item->barang->harga ?? 0;
            $nilai = ($item->status == 'disetujui') ? ($item->jumlah * $harga) : 0;

            $rejector = $item->rejector;
            $rejectedBy = $rejector ? $rejector->name : '-';
            $rejectedAt = $item->rejected_at ? \Carbon\Carbon::parse($item->rejected_at)->format('d M Y H:i') : '-';
            $alasan = $item->rejection_reason ?? $item->alasan_tolak ?? '-';

            return [
                'G-SC-' . $item->id_permintaan,
                \Carbon\Carbon::parse($item->tanggal_permintaan)->format('d M Y H:i'),
                $item->pemohon->name ?? '-',
                $unitName,
                $item->areaKerja ? ($item->areaKerja->nama_area . ' (' . ($item->areaKerja->bisnisUnit->nama_bisnis_unit ?? '-') . ')') : '-',
                $item->barang->nama_barang ?? '-',
                number_format($item->jumlah, 2),
                $item->barang->satuan ?? '-',
                $item->keterangan ?? '-',
                number_format($harga, 2),
                number_format($nilai, 2),
                $this->getStatusLabel($item->status),
                $item->approverL1->name ?? '-',
                $item->approved_l1_at ? \Carbon\Carbon::parse($item->approved_l1_at)->format('d M Y H:i') : '-',
                $item->approverAdmin->name ?? '-',
                $item->approved_admin_at ? \Carbon\Carbon::parse($item->approved_admin_at)->format('d M Y H:i') : '-',
                $rejectedBy,
                $rejectedAt,
                $alasan,
            ];
        })->toArray();
    }

    private function getStatusLabel($status)
    {
        $labels = [
            'pending_l1' => 'Menunggu L1',
            'pending_admin' => 'Menunggu Admin',
            'disetujui' => 'Disetujui',
            'ditolak' => 'Ditolak',
        ];
        return $labels[$status] ?? ucfirst($status);
    }
}