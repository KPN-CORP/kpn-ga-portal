<?php

namespace App\Http\Controllers\Antaran;

use App\Http\Controllers\Controller;
use App\Traits\AntaranHelpers;
use App\Traits\CompressesImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * ANTARAN — sisi PENGIRIM (buat kiriman, daftar, lacak, batal, kirim ulang).
 * Tugas kurir ada di AntaranKurirController, endpoint peta di AntaranTrackingController.
 *
 * Tabel & storage sengaja dibagi bareng modul Messenger (lihat AntaranHelpers /
 * CompressesImages) — cuma nama route, controller, dan view yang dipisah.
 */
class AntaranController extends Controller
{
    use AntaranHelpers, CompressesImages;

    /**
     * CATATAN: method ini SENGAJA ditulis langsung di sini (bukan cuma di trait
     * AntaranHelpers) supaya file controller ini berdiri sendiri kalau trait di
     * server belum ke-update. Kalau AntaranHelpers::getFileUrl() sudah ada &
     * ter-load duluan, method di trait itu yang menang (trait method override
     * lebih dulu dari method class induk, tapi karena ini didefinisikan langsung
     * di class, method inilah yang dipakai — itu memang tujuannya, biar aman).
     */
    private function getFileUrl(?string $filename, string $type = 'foto_barang'): ?string
    {
        if (!$filename) return null;
        return route('messenger.file', ['type' => $type, 'filename' => $filename]);
    }

    /**
     * Pemetaan tab -> daftar status, dipakai untuk pemisahan proses di halaman index.
     */
    private function tabStatusMap(): array
    {
        return [
            'proses'  => ['Belum Terkirim', 'Pengiriman Dibuat', 'Proses Pengiriman', 'Dokumen Belum Tersedia'],
            'selesai' => ['Terkirim'],
            'batal'   => ['Batal'],
        ];
    }

    public function index(Request $request)
    {
        $hasAccessAll = $this->hasAccessAll();
        $pelanggan = $this->currentPelanggan();

        $tabStatusMap = $this->tabStatusMap();
        $tab = $request->get('tab', 'semua');
        if (!isset($tabStatusMap[$tab])) {
            $tab = 'semua';
        }

        if (!$hasAccessAll && !$pelanggan) {
            return view('antaran.index', ['transaksi' => collect(), 'hasAccessAll' => $hasAccessAll, 'tab' => $tab]);
        }

        $query = DB::table('tb_transaksi as t')
            ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 't.pengirim')
            ->select('t.*', 'p.nama_pelanggan as nama_pengirim');

        if (!$hasAccessAll) {
            $query->where('t.pengirim', $pelanggan->id_pelanggan);
        }

        if ($request->filled('cari')) {
            $keyword = $request->cari;
            $query->where(function ($q) use ($keyword) {
                $q->where('t.no_transaksi', 'like', '%' . $keyword . '%')
                  ->orWhere('p.nama_pelanggan', 'like', '%' . $keyword . '%')
                  ->orWhere('t.penerima', 'like', '%' . $keyword . '%');
            });
        }

        if ($tab !== 'semua') {
            $query->whereIn('t.status', $tabStatusMap[$tab]);
        }

        $transaksi = $query->orderByDesc('t.created_at')->paginate(15)->withQueryString();

        return view('antaran.index', compact('transaksi', 'hasAccessAll', 'tab'));
    }

    /**
     * Export laporan Antaran ke Excel (.xlsx), detail lengkap.
     * range=bulan -> hanya bulan berjalan, range=semua -> seluruh data.
     * Tetap menghormati filter tab (status) & pencarian yang sedang aktif di halaman index.
     */
    public function exportExcel(Request $request)
    {
        $hasAccessAll = $this->hasAccessAll();
        $pelanggan = $this->currentPelanggan();

        if (!$hasAccessAll && !$pelanggan) {
            abort(403, 'Anda tidak memiliki akses untuk export laporan.');
        }

        $tabStatusMap = $this->tabStatusMap();
        $tab = $request->get('tab', 'semua');
        if (!isset($tabStatusMap[$tab])) {
            $tab = 'semua';
        }

        $range = $request->get('range', 'bulan'); // 'bulan' | 'semua'

        $query = DB::table('tb_transaksi as t')
            ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 't.pengirim')
            ->leftJoin('tb_pelanggan as k', 'k.id_pelanggan', '=', 't.kurir')
            ->select(
                't.*',
                'p.nama_pelanggan as nama_pengirim',
                'p.no_hp_pelanggan as no_hp_pengirim',
                'k.nama_pelanggan as nama_kurir',
                'k.no_hp_pelanggan as no_hp_kurir'
            );

        if (!$hasAccessAll) {
            $query->where('t.pengirim', $pelanggan->id_pelanggan);
        }

        if ($tab !== 'semua') {
            $query->whereIn('t.status', $tabStatusMap[$tab]);
        }

        if ($request->filled('cari')) {
            $keyword = $request->cari;
            $query->where(function ($q) use ($keyword) {
                $q->where('t.no_transaksi', 'like', '%' . $keyword . '%')
                  ->orWhere('p.nama_pelanggan', 'like', '%' . $keyword . '%')
                  ->orWhere('t.penerima', 'like', '%' . $keyword . '%');
            });
        }

        $labelPeriode = 'Semua';
        if ($range === 'bulan') {
            $awalBulan = now()->startOfMonth();
            $akhirBulan = now()->endOfMonth();
            $query->whereBetween('t.created_at', [$awalBulan, $akhirBulan]);
            $labelPeriode = $awalBulan->translatedFormat('F Y');
        }

        $transaksi = $query->orderBy('t.created_at')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Antaran');

        $header = [
            'No. Transaksi', 'Tanggal Dibuat', 'Status', 'Jenis Barang', 'Deskripsi',
            'Nama Pengirim', 'No. HP Pengirim', 'Alamat Asal', 'Link Maps Asal',
            'Nama Penerima', 'No. HP Penerima', 'Alamat Tujuan', 'Link Maps Tujuan',
            'Nama Kurir', 'No. HP Kurir', 'Riwayat Status', 'Catatan Penerima',
            'Penilaian', 'Komentar', 'Terakhir Diperbarui',
        ];
        $sheet->fromArray($header, null, 'A1');
        $sheet->getStyle('A1:T1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:T1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('2563EB');
        $sheet->freezePane('A2');

        $baris = 2;
        foreach ($transaksi as $item) {
            $riwayat = str_replace('<br>', "\n", $item->waktu ?? '');
            $sheet->fromArray([
                $item->no_transaksi,
                $item->created_at,
                $item->status,
                $item->nama_barang,
                $item->deskripsi,
                $item->nama_pengirim,
                $item->no_hp_pengirim,
                $item->alamat_asal,
                $item->maps_asal,
                $item->penerima,
                $item->no_hp_penerima,
                $item->alamat_tujuan,
                $item->maps_tujuan,
                $item->nama_kurir,
                $item->no_hp_kurir,
                $riwayat,
                $item->note_penerima,
                $item->penilaian,
                $item->komentar,
                $item->updated_at,
            ], null, 'A' . $baris);
            $sheet->getStyle('P' . $baris)->getAlignment()->setWrapText(true);
            $baris++;
        }

        foreach (range('A', 'T') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('P')->setAutoSize(false);
        $sheet->getColumnDimension('P')->setWidth(45);

        $tabLabel = ['semua' => 'Semua', 'proses' => 'Diproses', 'selesai' => 'Selesai', 'batal' => 'Dibatalkan'][$tab];
        $namaFile = 'Laporan-Antaran-' . Str::slug($labelPeriode) . '-' . Str::slug($tabLabel) . '-' . now()->format('Ymd-His') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $namaFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function request()
    {
        return view('antaran.request');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis_barang'     => 'required|in:paket,dokumen',
            'deskripsi'        => 'required|string|max:500',
            'alamat_asal'      => 'required|string|max:255',
            'maps_asal_input'  => 'nullable|url|max:500',
            'alamat_tujuan'    => 'required|string|max:255',
            'maps_tujuan_input'=> 'nullable|url|max:500',
            'penerima'         => 'required|string|max:100',
            'no_hp_penerima'   => 'required|string|regex:/^[0-9]{10,13}$/',
            'foto_barang'      => 'required|file|max:20480|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $pelanggan = $this->ensurePelanggan();
            $noTransaksi = 'AT' . date('YmdHis');

            $savedFileName = $this->storeCompressedOrMove(
                $request->file('foto_barang'),
                'messenger/foto_barang',
                'ant_' . date('YmdHis') . '_' . rand(1000, 9999)
            );

            // Pakai link Maps yang diisi manual kalau ada; kalau kosong, cari otomatis dari teks alamat.
            $mapsAsal = $request->filled('maps_asal_input')
                ? $request->maps_asal_input
                : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($request->alamat_asal);
            $mapsTujuan = $request->filled('maps_tujuan_input')
                ? $request->maps_tujuan_input
                : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($request->alamat_tujuan);

            DB::table('tb_transaksi')->insert([
                'no_transaksi'   => $noTransaksi,
                'pengirim'       => $pelanggan->id_pelanggan,
                'alamat_asal'    => $request->alamat_asal,
                'maps_asal'      => $mapsAsal,
                'alamat_tujuan'  => $request->alamat_tujuan,
                'maps_tujuan'    => $mapsTujuan,
                'penerima'       => $request->penerima,
                'no_hp_penerima' => $request->no_hp_penerima,
                'nama_barang'    => $request->jenis_barang,
                'deskripsi'      => $request->deskripsi,
                'foto_barang'    => $savedFileName,
                'status'         => 'Belum Terkirim',
                'kurir'          => 0,
                'waktu'          => $this->appendWaktu(null, 'Pengiriman Dibuat'),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            return redirect()->route('antaran.detail', $noTransaksi)
                ->with('success', 'Kiriman dibuat. No. resi: ' . $noTransaksi);
        } catch (\Exception $e) {
            Log::error('Antaran store error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
    }

    public function detail($no_transaksi)
    {
        $trx = DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->first();
        if (!$trx) abort(404, 'Resi tidak ditemukan');

        if (!$this->hasAccessAll()) {
            $pelanggan = $this->currentPelanggan();
            if ($pelanggan && $trx->pengirim != $pelanggan->id_pelanggan) {
                abort(403, 'Anda tidak memiliki akses ke resi ini');
            }
        }

        $pengirim = DB::table('tb_pelanggan')
            ->select('nama_pelanggan', 'no_hp_pelanggan')
            ->where('id_pelanggan', $trx->pengirim)->first();

        $kurir = $trx->kurir > 0
            ? DB::table('tb_pelanggan')->select('nama_pelanggan', 'no_hp_pelanggan')->where('id_pelanggan', $trx->kurir)->first()
            : null;

        // sama seperti Messenger: URL file dibuat lewat route messenger.file, bukan disimpan di DB
        $trx->foto_barang_url = $this->getFileUrl($trx->foto_barang ?? null, 'foto_barang');
        $trx->gambar_akhir_url = $this->getFileUrl($trx->gambar_akhir ?? null, 'gambar_akhir');

        $titikLokasi = DB::table('tb_lokasi_kurir')
            ->where('no_transaksi', $no_transaksi)
            ->orderBy('created_at')
            ->get(['latitude', 'longitude', 'created_at']);

        return view('antaran.detail', compact('trx', 'pengirim', 'kurir', 'titikLokasi'));
    }

    public function kirimUlang($no_transaksi)
    {
        $trx = DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->first();
        if (!$trx) return back()->with('error', 'Resi tidak ditemukan');

        if (!$this->hasAccessAll()) {
            $pelanggan = $this->currentPelanggan();
            if (!$pelanggan || $trx->pengirim != $pelanggan->id_pelanggan) abort(403);
        }
        if ($trx->status !== 'Dokumen Belum Tersedia') {
            return back()->with('error', 'Status harus "Dokumen Belum Tersedia".');
        }

        DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->update([
            'status'     => 'Belum Terkirim',
            'kurir'      => 0,
            'waktu'      => $this->appendWaktu($trx->waktu, 'Kirim Ulang (dokumen sudah tersedia)'),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Dikirim ulang, menunggu kurir.');
    }

    public function cancel($no_transaksi)
    {
        $trx = DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->first();
        if (!$trx) return back()->with('error', 'Resi tidak ditemukan');

        if (!$this->hasAccessAll()) {
            $pelanggan = $this->currentPelanggan();
            if (!$pelanggan || $trx->pengirim != $pelanggan->id_pelanggan) abort(403);
        }
        if (!in_array($trx->status, ['Belum Terkirim', 'Pengiriman Dibuat'])) {
            return back()->with('error', 'Hanya bisa dibatalkan sebelum diambil kurir.');
        }

        DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->update([
            'status'     => 'Batal',
            'waktu'      => $this->appendWaktu($trx->waktu, 'Batal'),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Kiriman dibatalkan.');
    }
}