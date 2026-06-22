<?php

namespace App\Http\Controllers\Supplies;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplies\SuppliesTransaksi;
use App\Models\Supplies\SuppliesBarang;
use App\Models\BisnisUnit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class SuppliesLaporanController extends Controller
{
    public function __construct()
    {
        $this->middleware('supplies.access:admin');
    }

    public function index(Request $request)
    {
        $tanggalAwal = $request->tanggal_awal ?: Carbon::now()->subMonth()->format('Y-m-d');
        $tanggalAkhir = $request->tanggal_akhir ?: Carbon::now()->format('Y-m-d');

        $query = SuppliesTransaksi::with([
                'barang', 'bisnisUnit', 'user',
                'permintaan.pemohon',      // untuk Keterangan Request & User Approve
                'permintaan.approver'      // User yang menyetujui
            ]);

        if ($request->id_bisnis_unit) {
            $query->where('id_bisnis_unit', $request->id_bisnis_unit);
        }
        if ($request->id_barang) {
            $query->where('id_barang', $request->id_barang);
        }
        $query->whereDate('tanggal', '>=', $tanggalAwal)
            ->whereDate('tanggal', '<=', $tanggalAkhir);

        $transaksi = $query->orderBy('tanggal', 'desc')->paginate(20);
        $barang = SuppliesBarang::all();
        $bisnisUnits = BisnisUnit::all();

        return view('supplies.laporan.index', compact('transaksi', 'barang', 'bisnisUnits', 'tanggalAwal', 'tanggalAkhir'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
            'id_bisnis_unit' => 'nullable|exists:tb_bisnis_unit,id_bisnis_unit',
            'id_barang' => 'nullable|exists:supplies_barang,id',
            'format' => 'required|in:pdf,excel'
        ]);

        // Gunakan relasi yang sama dengan index
        $query = SuppliesTransaksi::with([
                'barang', 'bisnisUnit', 'user',
                'permintaan.pemohon',
                'permintaan.approver'
            ]);

        if ($request->id_bisnis_unit) {
            $query->where('id_bisnis_unit', $request->id_bisnis_unit);
        }
        if ($request->id_barang) {
            $query->where('id_barang', $request->id_barang);
        }
        $query->whereDate('tanggal', '>=', $request->tanggal_awal)
            ->whereDate('tanggal', '<=', $request->tanggal_akhir);

        $transaksi = $query->orderBy('tanggal', 'desc')->get();

        // Hitung total nilai hanya untuk transaksi KELUAR
        $totalNilaiKeluar = $transaksi->filter(fn($t) => $t->jenis == 'keluar')->sum(function($t) {
            return $t->jumlah * ($t->barang->harga ?? 0);
        });

        $data = [
            'transaksi' => $transaksi,
            'filter' => [
                'tanggal_awal' => $request->tanggal_awal,
                'tanggal_akhir' => $request->tanggal_akhir,
                'bisnis_unit' => $request->id_bisnis_unit ? BisnisUnit::find($request->id_bisnis_unit)->nama_bisnis_unit : 'Semua Unit',
                'barang' => $request->id_barang ? SuppliesBarang::find($request->id_barang)->nama_barang : 'Semua Barang',
                'periode' => Carbon::parse($request->tanggal_awal)->format('d/m/Y') . ' - ' . Carbon::parse($request->tanggal_akhir)->format('d/m/Y'),
            ],
            'total_nilai' => $totalNilaiKeluar,
            'user' => auth()->user(),
        ];

        if ($request->format == 'pdf') {
            $pdf = Pdf::loadView('supplies.laporan.pdf', $data)->setPaper('a4', 'landscape');
            return $pdf->download('laporan_supplies_'.date('YmdHis').'.pdf');
        } else {
            // Excel (CSV) - format angka dengan koma sebagai pemisah ribuan
            $filename = 'laporan_supplies_'.date('YmdHis').'.csv';
            $handle = fopen('php://temp', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM UTF-8

            // Header
            fputcsv($handle, [
                'No-Request', 'Tanggal', 'Jenis', 'Barang', 'Jumlah', 
                'Harga (Rp)', 'Total (Rp)', 'Bisnis Unit',
                'Keperluan', 'Request', 'Noted Approve', 'Approve'
            ]);

            foreach ($transaksi as $t) {
                $harga = $t->barang->harga ?? 0;
                $total = ($t->jenis == 'keluar') ? $t->jumlah * $harga : 0;

                // Format angka untuk Excel: ribuan pakai koma, tanpa desimal
                $jumlahFormatted = number_format($t->jumlah, 0, ',', ',');
                $hargaFormatted = number_format($harga, 0, ',', ',');
                $totalFormatted = number_format($total, 0, ',', ',');

                fputcsv($handle, [
                    $t->no_ref ?? '-',
                    $t->tanggal->format('d/m/Y H:i'),
                    ucfirst($t->jenis),
                    $t->barang->nama_barang,
                    $jumlahFormatted . ' ' . $t->barang->satuan,
                    $hargaFormatted,
                    $totalFormatted,
                    $t->bisnisUnit->nama_bisnis_unit ?? '-',
                    $t->permintaan->keterangan ?? '-',
                    $t->permintaan->pemohon->name ?? '-',
                    $t->keterangan ?? '-',
                    $t->permintaan->approver->name ?? '-',
                ]);
            }

            // Baris total nilai keluar
            $totalNilaiFormatted = number_format($totalNilaiKeluar, 0, ',', ',');
            fputcsv($handle, []);
            fputcsv($handle, [
                'TOTAL NILAI KELUAR:', '', '', '', '', '', '', $totalNilaiFormatted, '', '', '', '', ''
            ]);

            rewind($handle);
            $csv = stream_get_contents($handle);
            fclose($handle);

            return Response::make($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }
    }
}