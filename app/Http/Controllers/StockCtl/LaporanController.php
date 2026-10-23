<?php

namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use App\Models\BisnisUnit;
use App\Models\StockCtl\AreaKerja;
use App\Models\StockCtl\Barang;
use App\Models\StockCtl\LaporanHistory;
use App\Models\StockCtl\Permintaan;
use App\Models\StockCtl\Stok;
use App\Models\StockCtl\Transaksi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Laporan Stock Control (ATK).
 *
 * Prasyarat:  composer require phpoffice/phpspreadsheet
 *
 * Alur: validasi + normalisasi filter -> buildReport() -> (preview JSON | file .xlsx).
 * Preview dan Excel memakai buildReport() yang sama, jadi isinya dijamin identik.
 */
class LaporanController extends Controller
{
    private const PREVIEW_LIMIT = 200;

    private const JENIS_LABEL = [
        'stok'       => 'Laporan Stok',
        'mutasi'     => 'Laporan Mutasi Barang',
        'permintaan' => 'Laporan Permintaan',
        'kartu_stok' => 'Kartu Stok',
    ];

    /** Warna badge (dipakai di Excel; preview punya mapping sendiri di blade). [fill, font] */
    private const BADGE_COLORS = [
        'Masih'            => ['C6EFCE', '006100'],
        'Disetujui'       => ['C6EFCE', '006100'],
        'Aktif'           => ['C6EFCE', '006100'],
        'Masuk'           => ['C6EFCE', '006100'],
        'Habis'           => ['FFC7CE', '9C0006'],
        'Ditolak'         => ['FFC7CE', '9C0006'],
        'Dibatalkan'      => ['FFC7CE', '9C0006'],
        'Keluar'          => ['FFC7CE', '9C0006'],
        'Menipis'         => ['FFEB9C', '9C5700'],
        'Menunggu L1'     => ['FFEB9C', '9C5700'],
        'Menunggu Admin'  => ['FFEB9C', '9C5700'],
        'Koreksi'         => ['FFEB9C', '9C5700'],
        'Transfer'        => ['DDEBF7', '1F4E78'],
        'Opname'          => ['E4DFEC', '5B3F86'],
    ];

    // =====================================================================
    //  HALAMAN
    // =====================================================================

    public function index()
    {
        $access  = session('stock_ctl_access');
        $isSuper = !empty($access['is_super']);

        $bisnisUnits = $isSuper
            ? BisnisUnit::orderBy('nama_bisnis_unit')->get()
            : BisnisUnit::where('id_bisnis_unit', $access['id_bisnis_unit'])->get();

        $areas = AreaKerja::with('bisnisUnit')
            ->when(!$isSuper, fn ($q) => $q->where('id_bisnis_unit', $access['id_bisnis_unit']))
            ->orderBy('nama_area')
            ->get()
            ->map(fn ($a) => [
                'id'      => $a->id_area_kerja,
                'nama'    => $a->nama_area,
                'bu'      => $a->id_bisnis_unit,
                'bu_nama' => $a->bisnisUnit->nama_bisnis_unit ?? '-',
            ])->values();

        $barang = Barang::orderBy('nama_barang')->get()
            ->map(fn ($b) => ['id' => $b->id_barang, 'label' => $b->kode_barang . ' - ' . $b->nama_barang])
            ->values();

        $historyQuery = LaporanHistory::with('user', 'area.bisnisUnit', 'barang')->orderBy('dicetak_pada', 'desc');
        if (!$isSuper) {
            $historyQuery->where('id_bisnis_unit', $access['id_bisnis_unit']);
        }
        $recentHistory = $historyQuery->limit(10)->get();

        $lockedBu = $isSuper ? null : (int) $access['id_bisnis_unit'];

        // Daftar "Unit" (divisi pemohon) untuk filter Laporan Permintaan.
        // Nama diambil sebelum " (" supaya sama dengan kolom Unit di laporan.
        $unitOptions = DB::table('stock_ctl_user_profil')
            ->whereNotNull('unit')
            ->where('unit', '<>', '')
            ->when(!$isSuper, fn ($q) => $q->where('id_bisnis_unit', $access['id_bisnis_unit']))
            ->get(['unit', 'id_bisnis_unit'])
            ->map(fn ($r) => ['nama' => trim(explode(' (', $r->unit)[0]), 'bu' => (int) $r->id_bisnis_unit])
            ->unique(fn ($u) => $u['bu'] . '|' . $u['nama'])
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('stock-ctl.laporan.index', compact(
            'bisnisUnits', 'areas', 'barang', 'recentHistory', 'isSuper', 'lockedBu', 'unitOptions'
        ));
    }

    public function history()
    {
        $access = session('stock_ctl_access');
        $query  = LaporanHistory::with('user', 'area.bisnisUnit', 'barang');

        if (empty($access['is_super'])) {
            $query->where('id_bisnis_unit', $access['id_bisnis_unit']);
        }

        $histories = $query->orderBy('dicetak_pada', 'desc')->paginate(20);
        return view('stock-ctl.laporan.history', compact('histories'));
    }

    // =====================================================================
    //  ENDPOINT
    // =====================================================================

    /** Preview (AJAX) -> JSON. Tidak menyimpan riwayat dan tidak membuat file. */
    public function preview(Request $request)
    {
        ini_set('memory_limit', '512M');
        set_time_limit(120);

        $f      = $this->resolveFilters($request);
        $report = $this->buildReport($f);

        $totalRows = count($report['rows']);
        $rows      = array_slice($report['rows'], 0, self::PREVIEW_LIMIT);

        $displayRows = array_map(function ($row) use ($report) {
            $out = [];
            foreach ($report['columns'] as $i => $col) {
                $out[] = $this->display($col['type'], $row[$i] ?? null);
            }
            return $out;
        }, $rows);

        $totals = null;
        if (!empty($report['totals']) && $totalRows > 0) {
            $totals = [];
            foreach ($report['columns'] as $i => $col) {
                $totals[] = array_key_exists($i, $report['totals'])
                    ? $this->display($col['type'], $report['totals'][$i])
                    : '';
            }
        }

        return response()->json([
            'title'      => $report['title'],
            'filters'    => $f['info'],
            'summary'    => array_map(fn ($s) => [
                'label' => $s['label'],
                'value' => $this->display($s['type'], $s['value']),
            ], $report['summary']),
            'columns'    => array_map(fn ($c) => ['label' => $c['label'], 'type' => $c['type']], $report['columns']),
            'rows'       => $displayRows,
            'totals'     => $totals,
            'total_rows' => $totalRows,
            'truncated'  => $totalRows > self::PREVIEW_LIMIT,
        ]);
    }

    /** Download laporan sebagai file Excel (.xlsx) asli. */
    public function excel(Request $request)
    {
        ini_set('memory_limit', '768M');
        set_time_limit(300);

        $f      = $this->resolveFilters($request);
        $report = $this->buildReport($f);

        $filename = $report['filename_prefix'] . '_' . date('Ymd_His') . '.xlsx';
        $this->saveHistory($f, $filename);

        $spreadsheet = $this->buildSpreadsheet($report, $f);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    // =====================================================================
    //  FILTER: validasi, otorisasi, normalisasi
    // =====================================================================

    private function resolveFilters(Request $request): array
    {
        $request->validate([
            'jenis'             => 'required|in:stok,mutasi,permintaan,kartu_stok',
            'id_bisnis_unit'    => 'nullable|integer|exists:tb_bisnis_unit,id_bisnis_unit',
            'id_area'           => 'nullable|exists:stock_ctl_area_kerja,id_area_kerja',
            'id_barang'         => 'nullable|exists:stock_ctl_barang,id_barang',
            'jenis_mutasi'      => 'nullable|in:masuk,keluar,transfer,opname',
            'status_transaksi'  => 'nullable|in:aktif,dibatalkan,koreksi',
            'status_permintaan' => 'nullable|in:pending_l1,pending_admin,disetujui,ditolak',
            'unit'              => 'nullable|string|max:150',
            'status_stok'       => 'nullable|in:masih,menipis,habis',
            'tanggal_awal'      => 'nullable|date',
            'tanggal_akhir'     => 'nullable|date|after_or_equal:tanggal_awal',
        ], [
            'tanggal_akhir.after_or_equal' => 'Periode akhir tidak boleh lebih awal dari periode awal.',
        ]);

        if ($request->jenis === 'kartu_stok') {
            $request->validate([
                'id_area'   => 'required',
                'id_barang' => 'required',
            ], [
                'id_area.required'   => 'Kartu Stok wajib memilih 1 Area Kerja.',
                'id_barang.required' => 'Kartu Stok wajib memilih 1 Barang.',
            ]);
        }

        $access  = session('stock_ctl_access');
        $isSuper = !empty($access['is_super']);

        // Non-super selalu terkunci ke Bisnis Unit miliknya.
        $idBu = $isSuper
            ? ($request->filled('id_bisnis_unit') ? (int) $request->id_bisnis_unit : null)
            : (int) $access['id_bisnis_unit'];

        $area = null;
        if ($request->filled('id_area')) {
            $area = AreaKerja::with('bisnisUnit')->find($request->id_area);
            if (!$area) {
                abort(422, 'Area kerja tidak ditemukan.');
            }
            if (!$isSuper && $area->id_bisnis_unit != $access['id_bisnis_unit']) {
                abort(403, 'Anda tidak memiliki akses ke area tersebut.');
            }
            if ($idBu && $area->id_bisnis_unit != $idBu) {
                abort(422, 'Area kerja yang dipilih bukan bagian dari Bisnis Unit tersebut.');
            }
            $idBu = (int) $area->id_bisnis_unit;
        }

        $bu     = $idBu ? BisnisUnit::find($idBu) : null;
        $barang = $request->filled('id_barang') ? Barang::find($request->id_barang) : null;

        // Daftar area yang dicakup laporan (null = semua area).
        $scopeAreaIds = null;
        if ($area) {
            $scopeAreaIds = [(int) $area->id_area_kerja];
        } elseif ($idBu) {
            $scopeAreaIds = AreaKerja::where('id_bisnis_unit', $idBu)->pluck('id_area_kerja')->map(fn ($v) => (int) $v)->all();
        }

        $jenis = $request->jenis;

        $f = [
            'jenis'             => $jenis,
            'is_super'          => $isSuper,
            'access'            => $access,
            'id_bu'             => $idBu,
            'bu'                => $bu,
            'area'              => $area,
            'id_area'           => $area->id_area_kerja ?? null,
            'barang'            => $barang,
            'id_barang'         => $barang->id_barang ?? null,
            'scope_area_ids'    => $scopeAreaIds,
            'jenis_mutasi'      => $request->filled('jenis_mutasi') ? $request->jenis_mutasi : null,
            // Default "aktif" supaya transaksi yang dibatalkan/dikoreksi tidak menggelembungkan total.
            'status_transaksi'  => $request->has('status_transaksi') ? ($request->status_transaksi ?: null) : 'aktif',
            'status_permintaan' => $request->filled('status_permintaan') ? $request->status_permintaan : null,
            'unit'              => $request->filled('unit') ? trim($request->unit) : null,
            'status_stok'       => $request->filled('status_stok') ? $request->status_stok : null,
            'tanggal_awal'      => $request->filled('tanggal_awal') ? $request->tanggal_awal : null,
            'tanggal_akhir'     => $request->filled('tanggal_akhir') ? $request->tanggal_akhir : null,
        ];

        $f['info'] = $this->buildFilterInfo($f);
        return $f;
    }

    /** Daftar [label, nilai] filter aktif; dipakai di header Excel dan chip di preview. */
    private function buildFilterInfo(array $f): array
    {
        $info = [];
        $info[] = ['Bisnis Unit', $f['bu']->nama_bisnis_unit ?? 'Semua Bisnis Unit'];
        $info[] = ['Area Kerja', $f['area'] ? $f['area']->nama_area : 'Semua Area'];

        if ($f['jenis'] !== 'stok' || $f['barang']) {
            $info[] = ['Barang', $f['barang'] ? $f['barang']->kode_barang . ' - ' . $f['barang']->nama_barang : 'Semua Barang'];
        }

        if ($f['jenis'] === 'mutasi') {
            $info[] = ['Jenis Transaksi', $f['jenis_mutasi'] ? $this->jenisLabel($f['jenis_mutasi']) : 'Semua Jenis'];
            $info[] = ['Status Transaksi', $f['status_transaksi'] ? $this->getStatusTransaksiLabel($f['status_transaksi']) : 'Semua Status'];
        }
        if ($f['jenis'] === 'permintaan') {
            $info[] = ['Status', $f['status_permintaan'] ? $this->getStatusLabel($f['status_permintaan']) : 'Semua Status'];
            $info[] = ['Unit', $f['unit'] ?: 'Semua Unit'];
        }
        if ($f['jenis'] === 'stok') {
            $info[] = ['Status Stok', $f['status_stok'] ? ucfirst($f['status_stok']) : 'Semua Status'];
        }

        if ($f['jenis'] === 'stok') {
            $info[] = ['Periode', 'Posisi stok saat dicetak'];
        } else {
            $awal  = $f['tanggal_awal'] ? Carbon::parse($f['tanggal_awal'])->format('d/m/Y') : null;
            $akhir = $f['tanggal_akhir'] ? Carbon::parse($f['tanggal_akhir'])->format('d/m/Y') : null;
            $info[] = ['Periode', match (true) {
                $awal && $akhir => "$awal s/d $akhir",
                (bool) $awal    => "Mulai $awal",
                (bool) $akhir   => "Sampai $akhir",
                default         => 'Semua periode',
            }];
        }

        return $info;
    }

    // =====================================================================
    //  DATA LAPORAN
    //  Struktur report:
    //    title, sheet, filename_prefix,
    //    columns[]  : label, type (text|badge|datetime|qty|money), width
    //    rows[]     : nilai mentah (angka = float, tanggal = 'Y-m-d H:i:s')
    //    summary[]  : label, value, type (int|qty|money|text)
    //    totals     : [idxKolom => nilai]  (kolom yang dijumlah di baris TOTAL)
    //    sum_cols   : idx kolom yang pakai rumus SUBTOTAL di Excel
    //    bold_first_row : baris pertama ditebalkan (Saldo Awal)
    // =====================================================================

    private function buildReport(array $f): array
    {
        return match ($f['jenis']) {
            'stok'       => $this->reportStok($f),
            'mutasi'     => $this->reportMutasi($f),
            'permintaan' => $this->reportPermintaan($f),
            'kartu_stok' => $this->reportKartuStok($f),
            default      => abort(400),
        };
    }

    // ---------- STOK ----------

    private function reportStok(array $f): array
    {
        $query = Stok::with('barang', 'areaKerja.bisnisUnit');

        if ($f['scope_area_ids'] !== null) {
            $query->whereIn('id_area_kerja', $f['scope_area_ids']);
        }
        if ($f['id_barang']) {
            $query->where('id_barang', $f['id_barang']);
        }

        $stok = $query->get()->filter(function ($s) use ($f) {
            return match ($f['status_stok']) {
                'habis'   => $s->jumlah <= 0,
                'menipis' => $s->jumlah > 0 && $s->jumlah <= $s->stok_minimum,
                'masih'    => $s->jumlah > $s->stok_minimum,
                default   => true,
            };
        })->sortBy([
            fn ($a, $b) => strcmp($a->areaKerja->bisnisUnit->nama_bisnis_unit ?? '', $b->areaKerja->bisnisUnit->nama_bisnis_unit ?? ''),
            fn ($a, $b) => strcmp($a->areaKerja->nama_area ?? '', $b->areaKerja->nama_area ?? ''),
            fn ($a, $b) => strcmp($a->barang->nama_barang ?? '', $b->barang->nama_barang ?? ''),
        ])->values();

        $rows = [];
        $totalNilai = 0;
        $menipis = $habis = 0;

        foreach ($stok as $s) {
            $harga = (float) ($s->barang->harga ?? 0);
            $jml   = (float) $s->jumlah;
            $min   = (float) $s->stok_minimum;
            $nilai = $jml * $harga;
            $totalNilai += $nilai;

            if ($jml <= 0) {
                $status = 'Habis';
                $habis++;
            } elseif ($jml <= $min) {
                $status = 'Menipis';
                $menipis++;
            } else {
                $status = 'Masih';
            }

            $rows[] = [
                $s->areaKerja->bisnisUnit->nama_bisnis_unit ?? '-',
                $s->areaKerja->nama_area ?? '-',
                $s->barang->kode_barang ?? '-',
                $s->barang->nama_barang ?? '-',
                $s->barang->satuan ?? '-',
                $jml,
                $harga,
                $nilai,
                $min,
                $status,
                $s->last_update ? $s->last_update->format('Y-m-d H:i:s') : null,
            ];
        }

        return [
            'title'           => 'Laporan Stok Barang',
            'sheet'           => 'Stok',
            'filename_prefix' => 'laporan_stok',
            'columns' => [
                ['label' => 'Bisnis Unit',     'type' => 'text',     'width' => 24],
                ['label' => 'Area Kerja',      'type' => 'text',     'width' => 24],
                ['label' => 'Kode Barang',     'type' => 'text',     'width' => 16],
                ['label' => 'Nama Barang',     'type' => 'text',     'width' => 38],
                ['label' => 'Satuan',          'type' => 'text',     'width' => 10],
                ['label' => 'Stok',            'type' => 'qty',      'width' => 12],
                ['label' => 'Harga (Rp)',      'type' => 'money',    'width' => 16],
                ['label' => 'Nilai (Rp)',      'type' => 'money',    'width' => 18],
                ['label' => 'Stok Minimum',    'type' => 'qty',      'width' => 14],
                ['label' => 'Status',          'type' => 'badge',    'width' => 12],
                ['label' => 'Update Terakhir', 'type' => 'datetime', 'width' => 18],
            ],
            'rows'     => $rows,
            'summary'  => [
                ['label' => 'Jumlah Item',      'value' => count($rows),  'type' => 'int'],
                ['label' => 'Total Nilai Stok', 'value' => $totalNilai,   'type' => 'money'],
                ['label' => 'Item Menipis',     'value' => $menipis,      'type' => 'int'],
                ['label' => 'Item Habis',       'value' => $habis,        'type' => 'int'],
            ],
            'totals'   => [7 => $totalNilai],
            'sum_cols' => [7],
        ];
    }

    // ---------- MUTASI ----------

    private function reportMutasi(array $f): array
    {
        $query = Transaksi::with('barang', 'areaAsal.bisnisUnit', 'areaTujuan.bisnisUnit', 'user');

        if ($f['scope_area_ids'] !== null) {
            $ids = $f['scope_area_ids'];
            $query->where(function ($q) use ($ids) {
                $q->whereIn('id_area_asal', $ids)->orWhereIn('id_area_tujuan', $ids);
            });
        }
        if ($f['id_barang']) {
            $query->where('id_barang', $f['id_barang']);
        }
        if ($f['jenis_mutasi']) {
            $query->where('jenis', $f['jenis_mutasi']);
        }
        if ($f['status_transaksi']) {
            $query->where('status', $f['status_transaksi']);
        }
        if ($f['tanggal_awal']) {
            $query->whereDate('tanggal', '>=', $f['tanggal_awal']);
        }
        if ($f['tanggal_akhir']) {
            $query->whereDate('tanggal', '<=', $f['tanggal_akhir']);
        }

        $transaksi = $query->orderBy('tanggal', 'desc')->orderBy('id_transaksi', 'desc')->get();

        $rows = [];
        $totalNilai = 0;
        $nilaiPerJenis = ['masuk' => 0, 'keluar' => 0, 'transfer' => 0, 'opname' => 0];

        foreach ($transaksi as $t) {
            $harga = (float) ($t->barang->harga ?? 0);
            $jml   = (float) $t->jumlah;
            $nilai = $jml * $harga;
            $totalNilai += $nilai;
            if (isset($nilaiPerJenis[$t->jenis])) {
                $nilaiPerJenis[$t->jenis] += $nilai;
            }

            $rows[] = [
                $t->tanggal ? Carbon::parse($t->tanggal)->format('Y-m-d H:i:s') : null,
                $t->no_ref ?? '-',
                $this->jenisLabel($t->jenis),
                $t->barang->kode_barang ?? '-',
                $t->barang->nama_barang ?? '-',
                $jml,
                $t->barang->satuan ?? '-',
                $harga,
                $nilai,
                $this->areaLabel($t->areaAsal),
                $this->areaLabel($t->areaTujuan),
                $t->keterangan ?: '-',
                $t->user->name ?? '-',
                $this->getStatusTransaksiLabel($t->status),
            ];
        }

        return [
            'title'           => 'Laporan Mutasi Barang',
            'sheet'           => 'Mutasi',
            'filename_prefix' => 'laporan_mutasi',
            'columns' => [
                ['label' => 'Tanggal',      'type' => 'datetime', 'width' => 18],
                ['label' => 'No. Ref',      'type' => 'text',     'width' => 20],
                ['label' => 'Jenis',        'type' => 'badge',    'width' => 12],
                ['label' => 'Kode Barang',  'type' => 'text',     'width' => 16],
                ['label' => 'Nama Barang',  'type' => 'text',     'width' => 36],
                ['label' => 'Jumlah',       'type' => 'qty',      'width' => 12],
                ['label' => 'Satuan',       'type' => 'text',     'width' => 10],
                ['label' => 'Harga (Rp)',   'type' => 'money',    'width' => 16],
                ['label' => 'Nilai (Rp)',   'type' => 'money',    'width' => 18],
                ['label' => 'Area Asal',    'type' => 'text',     'width' => 30],
                ['label' => 'Area Tujuan',  'type' => 'text',     'width' => 30],
                ['label' => 'Keterangan',   'type' => 'text',     'width' => 34],
                ['label' => 'User',         'type' => 'text',     'width' => 20],
                ['label' => 'Status',       'type' => 'badge',    'width' => 13],
            ],
            'rows'     => $rows,
            'summary'  => [
                ['label' => 'Total Transaksi', 'value' => count($rows),               'type' => 'int'],
                ['label' => 'Nilai Masuk',     'value' => $nilaiPerJenis['masuk'],    'type' => 'money'],
                ['label' => 'Nilai Keluar',    'value' => $nilaiPerJenis['keluar'],   'type' => 'money'],
                ['label' => 'Nilai Transfer',  'value' => $nilaiPerJenis['transfer'], 'type' => 'money'],
            ],
            'totals'   => [8 => $totalNilai],
            'sum_cols' => [8],
        ];
    }

    // ---------- PERMINTAAN ----------

    private function reportPermintaan(array $f): array
    {
        $query = Permintaan::with(
            'pemohon.profil', 'barang', 'areaKerja.bisnisUnit',
            'approverL1', 'approverAdmin', 'rejector',
            'substitusi.admin', 'substitusi.barangPengganti'
        );

        if (!$f['is_super']) {
            $idBuAkses = $f['access']['id_bisnis_unit'];
            $query->whereExists(function ($q) use ($idBuAkses) {
                $q->select(DB::raw(1))
                    ->from('stock_ctl_user_profil')
                    ->whereColumn('stock_ctl_user_profil.id_user', 'stock_ctl_permintaan.id_user_pemohon')
                    ->where('stock_ctl_user_profil.id_bisnis_unit', $idBuAkses);
            });
        }

        if ($f['id_area']) {
            $query->where('id_area_kerja', $f['id_area']);
        } elseif ($f['is_super'] && $f['scope_area_ids'] !== null) {
            $query->whereIn('id_area_kerja', $f['scope_area_ids']);
        }
        if ($f['id_barang']) {
            $query->where('id_barang', $f['id_barang']);
        }
        if ($f['status_permintaan']) {
            $query->where('status', $f['status_permintaan']);
        }
        if ($f['unit']) {
            // cocok persis ("GA") atau diikuti keterangan ("GA (....)")
            $unit = $f['unit'];
            $like = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $unit) . ' (%';
            $query->whereExists(function ($q) use ($unit, $like) {
                $q->select(DB::raw(1))
                    ->from('stock_ctl_user_profil')
                    ->whereColumn('stock_ctl_user_profil.id_user', 'stock_ctl_permintaan.id_user_pemohon')
                    ->where(function ($w) use ($unit, $like) {
                        $w->where('stock_ctl_user_profil.unit', $unit)
                          ->orWhere('stock_ctl_user_profil.unit', 'like', $like);
                    });
            });
        }
        if ($f['tanggal_awal']) {
            $query->whereDate('tanggal_permintaan', '>=', $f['tanggal_awal']);
        }
        if ($f['tanggal_akhir']) {
            $query->whereDate('tanggal_permintaan', '<=', $f['tanggal_akhir']);
        }

        // Urut berdasarkan No. Permintaan (kode unit, tahun, nomor urut) - nomor dibandingkan sebagai
        // angka, jadi 99 < 100 (urutan teks biasa akan salah). Nomor di luar format ditaruh di akhir.
        $permintaan = $query->orderBy('id_permintaan')->get()->sort(function ($a, $b) {
            $kunci = function ($p) {
                if (preg_match('/^(.+)-(\d{2})-(\d+)$/', (string) $p->no_tampil, $m)) {
                    return [0, $m[1], (int) $m[2], (int) $m[3]];
                }
                return [1, '', 0, (int) $p->id_permintaan];
            };
            return $kunci($a) <=> $kunci($b);
        })->values();

        $rows = [];
        $totalNilai = 0;
        $cnt = ['disetujui' => 0, 'ditolak' => 0, 'pending' => 0];

        $fmtDt = fn ($v) => $v ? Carbon::parse($v)->format('Y-m-d H:i:s') : null;

        foreach ($permintaan as $p) {
            $unit     = optional($p->pemohon->profil ?? null)->unit ?? '';
            $unitName = $unit ? explode(' (', $unit)[0] : '-';
            $harga    = (float) ($p->barang->harga ?? 0);
            $jml      = (float) $p->jumlah;
            $sub      = $p->substitusi;
            $hargaPengganti = $sub ? (float) ($sub->barangPengganti->harga ?? 0) : 0;
            // Jika barang diganti, nilai dihitung dari barang pengganti yang benar-benar keluar
            $nilai    = $p->status === 'disetujui'
                ? ($sub ? (float) $sub->jumlah_pengganti * $hargaPengganti : $jml * $harga)
                : 0;
            $totalNilai += $nilai;

            if ($p->status === 'disetujui') {
                $cnt['disetujui']++;
            } elseif ($p->status === 'ditolak') {
                $cnt['ditolak']++;
            } else {
                $cnt['pending']++;
            }

            $rows[] = [
                $p->no_tampil,
                $fmtDt($p->tanggal_permintaan),
                $p->pemohon->name ?? '-',
                $unitName,
                $this->areaLabel($p->areaKerja),
                $p->barang->nama_barang ?? '-',
                $jml,
                $p->barang->satuan ?? '-',
                $p->keterangan ?: '-',
                $harga,
                $nilai,
                $this->getStatusLabel($p->status),
                $p->approverL1->name ?? '-',
                $fmtDt($p->approved_l1_at),
                $p->approverAdmin->name ?? '-',
                $fmtDt($p->approved_admin_at),
                $p->rejector->name ?? '-',
                $fmtDt($p->rejected_at),
                $p->rejection_reason ?: ($p->alasan_tolak ?: '-'),
                $sub ? $sub->nama_pengganti : '-',
                $sub ? (float) $sub->jumlah_pengganti : null,
                $sub ? ($sub->barangPengganti->satuan ?? '-') : '-',
                $sub ? $hargaPengganti : null,
                $sub ? $sub->alasan : '-',
                $sub ? ($sub->admin->name ?? '-') : '-',
                $sub ? $fmtDt($sub->created_at) : null,
            ];
        }

        return [
            'title'           => 'Laporan Permintaan Barang',
            'sheet'           => 'Permintaan',
            'filename_prefix' => 'laporan_permintaan',
            'columns' => [
                ['label' => 'No. Permintaan',    'type' => 'text',     'width' => 16],
                ['label' => 'Tanggal',           'type' => 'datetime', 'width' => 18],
                ['label' => 'Pemohon',           'type' => 'text',     'width' => 24],
                ['label' => 'Unit',              'type' => 'text',     'width' => 22],
                ['label' => 'Area',              'type' => 'text',     'width' => 30],
                ['label' => 'Barang',            'type' => 'text',     'width' => 34],
                ['label' => 'Jumlah',            'type' => 'qty',      'width' => 12],
                ['label' => 'Satuan',            'type' => 'text',     'width' => 10],
                ['label' => 'Keterangan',        'type' => 'text',     'width' => 32],
                ['label' => 'Harga (Rp)',        'type' => 'money',    'width' => 16],
                ['label' => 'Nilai (Rp)',        'type' => 'money',    'width' => 18],
                ['label' => 'Status',            'type' => 'badge',    'width' => 16],
                ['label' => 'Approver L1',       'type' => 'text',     'width' => 22],
                ['label' => 'Approved L1',       'type' => 'datetime', 'width' => 18],
                ['label' => 'Approver Admin',    'type' => 'text',     'width' => 22],
                ['label' => 'Approved Admin',    'type' => 'datetime', 'width' => 18],
                ['label' => 'Ditolak Oleh',      'type' => 'text',     'width' => 22],
                ['label' => 'Ditolak Pada',      'type' => 'datetime', 'width' => 18],
                ['label' => 'Alasan Penolakan',  'type' => 'text',     'width' => 34],
                ['label' => 'Barang Pengganti',  'type' => 'text',     'width' => 34],
                ['label' => 'Jumlah Pengganti',  'type' => 'qty',      'width' => 14],
                ['label' => 'Satuan Pengganti',  'type' => 'text',     'width' => 12],
                ['label' => 'Harga Pengganti (Rp)', 'type' => 'money', 'width' => 18],
                ['label' => 'Alasan Penggantian','type' => 'text',     'width' => 34],
                ['label' => 'Diganti Oleh',      'type' => 'text',     'width' => 22],
                ['label' => 'Tanggal Penggantian','type' => 'datetime','width' => 18],
            ],
            'rows'     => $rows,
            'summary'  => [
                ['label' => 'Total Permintaan',  'value' => count($rows),         'type' => 'int'],
                ['label' => 'Disetujui',         'value' => $cnt['disetujui'],    'type' => 'int'],
                ['label' => 'Ditolak',           'value' => $cnt['ditolak'],      'type' => 'int'],
                ['label' => 'Dalam Proses',      'value' => $cnt['pending'],      'type' => 'int'],
                ['label' => 'Nilai Disetujui',   'value' => $totalNilai,          'type' => 'money'],
            ],
            'totals'   => [10 => $totalNilai],
            'sum_cols' => [10],
        ];
    }

    // ---------- KARTU STOK ----------

    private function reportKartuStok(array $f): array
    {
        $areaId   = $f['id_area'];
        $barangId = $f['id_barang'];
        $barang   = $f['barang'];
        $area     = $f['area'];

        $base = Transaksi::where('id_barang', $barangId);

        // Saldo awal = akumulasi semua transaksi sebelum periode awal
        // (stok awal pun tercatat sebagai transaksi 'masuk').
        $saldoAwal = 0.0;
        if ($f['tanggal_awal']) {
            $masuk = (clone $base)->where('id_area_tujuan', $areaId)->whereDate('tanggal', '<', $f['tanggal_awal'])->sum('jumlah');
            $keluar = (clone $base)->where('id_area_asal', $areaId)->whereDate('tanggal', '<', $f['tanggal_awal'])->sum('jumlah');
            $saldoAwal = (float) $masuk - (float) $keluar;
        }

        $periode = (clone $base)->where(function ($q) use ($areaId) {
            $q->where('id_area_asal', $areaId)->orWhere('id_area_tujuan', $areaId);
        });
        if ($f['tanggal_awal']) {
            $periode->whereDate('tanggal', '>=', $f['tanggal_awal']);
        }
        if ($f['tanggal_akhir']) {
            $periode->whereDate('tanggal', '<=', $f['tanggal_akhir']);
        }
        $transaksi = $periode->with('user')->orderBy('tanggal')->orderBy('id_transaksi')->get();

        $rows = [];
        $rows[] = [null, '', 'Saldo Awal', null, null, $saldoAwal, '', '', ''];

        $saldo = $saldoAwal;
        $totMasuk = $totKeluar = 0.0;

        foreach ($transaksi as $t) {
            $masuk  = $t->id_area_tujuan == $areaId ? (float) $t->jumlah : 0.0;
            $keluar = $t->id_area_asal == $areaId ? (float) $t->jumlah : 0.0;
            $saldo += $masuk - $keluar;
            $totMasuk += $masuk;
            $totKeluar += $keluar;

            $rows[] = [
                $t->tanggal ? Carbon::parse($t->tanggal)->format('Y-m-d H:i:s') : null,
                $t->no_ref ?? '-',
                $this->jenisLabel($t->jenis),
                $masuk ?: null,
                $keluar ?: null,
                $saldo,
                $t->keterangan ?: '-',
                $t->user->name ?? '-',
                $this->getStatusTransaksiLabel($t->status),
            ];
        }

        $satuan = $barang->satuan ?? '';

        return [
            'title'           => 'Kartu Stok - ' . ($barang->nama_barang ?? ''),
            'sheet'           => 'Kartu Stok',
            'filename_prefix' => 'kartu_stok_' . Str::slug($barang->nama_barang ?? 'barang'),
            'columns' => [
                ['label' => 'Tanggal',    'type' => 'datetime', 'width' => 18],
                ['label' => 'No. Ref',    'type' => 'text',     'width' => 22],
                ['label' => 'Jenis',      'type' => 'badge',    'width' => 14],
                ['label' => 'Masuk',      'type' => 'qty',      'width' => 13],
                ['label' => 'Keluar',     'type' => 'qty',      'width' => 13],
                ['label' => 'Saldo',      'type' => 'qty',      'width' => 13],
                ['label' => 'Keterangan', 'type' => 'text',     'width' => 40],
                ['label' => 'User',       'type' => 'text',     'width' => 22],
                ['label' => 'Status',     'type' => 'badge',    'width' => 13],
            ],
            'rows'     => $rows,
            'summary'  => [
                ['label' => 'Saldo Awal' . ($satuan ? " ($satuan)" : ''),  'value' => $saldoAwal, 'type' => 'qty'],
                ['label' => 'Total Masuk',  'value' => $totMasuk,  'type' => 'qty'],
                ['label' => 'Total Keluar', 'value' => $totKeluar, 'type' => 'qty'],
                ['label' => 'Saldo Akhir',  'value' => $saldo,     'type' => 'qty'],
            ],
            'totals'         => [3 => $totMasuk, 4 => $totKeluar, 5 => $saldo],
            'sum_cols'       => [3, 4],        // kolom Saldo (5) bukan jumlah, diisi saldo akhir statis
            'bold_first_row' => true,
        ];
    }

    // =====================================================================
    //  EXCEL (.xlsx)
    // =====================================================================

    private function buildSpreadsheet(array $report, array $f): Spreadsheet
    {
        $user = auth()->user()->name ?? '-';
        $now  = Carbon::now('Asia/Jakarta');

        $ss = new Spreadsheet();
        $ss->getProperties()
            ->setCreator($user)
            ->setTitle($report['title'])
            ->setSubject('Stock Control ATK')
            ->setCompany('GA Portal');

        $ws = $ss->getActiveSheet();
        $ws->setTitle(substr($report['sheet'], 0, 31));
        $ws->setShowGridlines(false);

        $cols = $report['columns'];
        $n    = count($cols);
        $last = Coordinate::stringFromColumnIndex($n);
        $L    = fn (int $i) => Coordinate::stringFromColumnIndex($i);

        foreach ($cols as $i => $c) {
            $ws->getColumnDimension($L($i + 1))->setWidth($c['width']);
        }

        // ---- Judul ----
        $r = 1;
        $ws->setCellValue("A{$r}", mb_strtoupper($report['title']));
        $ws->mergeCells("A{$r}:{$last}{$r}");
        $ws->getStyle("A{$r}")->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('1F3864');
        $ws->getRowDimension($r)->setRowHeight(26);
        $ws->getStyle("A{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // ---- Info filter (3 filter per baris) ----
        $r++;
        $chunks = array_chunk($f['info'], 3);
        foreach ($chunks as $chunk) {
            $text = implode('      |      ', array_map(fn ($p) => $p[0] . ': ' . $p[1], $chunk));
            $ws->setCellValueExplicit("A{$r}", $text, DataType::TYPE_STRING);
            $ws->mergeCells("A{$r}:{$last}{$r}");
            $ws->getStyle("A{$r}")->getFont()->setSize(10)->getColor()->setRGB('404040');
            $r++;
        }
        $ws->setCellValueExplicit(
            "A{$r}",
            'Dicetak oleh: ' . $user . '      |      ' . $now->format('d/m/Y H:i') . ' WIB',
            DataType::TYPE_STRING
        );
        $ws->mergeCells("A{$r}:{$last}{$r}");
        $ws->getStyle("A{$r}")->getFont()->setSize(9)->setItalic(true)->getColor()->setRGB('7F7F7F');
        $r += 2;

        // ---- Kartu ringkasan (KPI) ----
        $summary = $report['summary'];
        if (count($summary) > 0) {
            $span = max(1, min(3, intdiv($n, count($summary))));
            $labelRow = $r;
            $valueRow = $r + 1;
            $col = 1;
            foreach ($summary as $s) {
                $a = $L($col);
                $b = $L($col + $span - 1);

                $ws->setCellValueExplicit("{$a}{$labelRow}", $s['label'], DataType::TYPE_STRING);
                $ws->mergeCells("{$a}{$labelRow}:{$b}{$labelRow}");
                $ws->getStyle("{$a}{$labelRow}:{$b}{$labelRow}")->applyFromArray([
                    'font'      => ['size' => 9, 'color' => ['rgb' => '595959']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F8']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFC9D9']]],
                ]);

                $ws->setCellValue("{$a}{$valueRow}", (float) $s['value']);
                $ws->mergeCells("{$a}{$valueRow}:{$b}{$valueRow}");
                $ws->getStyle("{$a}{$valueRow}")->getNumberFormat()->setFormatCode(
                    $s['type'] === 'int' ? '#,##0' : '#,##0.00'
                );
                $ws->getStyle("{$a}{$valueRow}:{$b}{$valueRow}")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1F3864']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFC9D9']]],
                ]);

                $col += $span;
            }
            $ws->getRowDimension($valueRow)->setRowHeight(24);
            $r = $valueRow + 2;
        }

        // ---- Header tabel ----
        $hr = $r;
        foreach ($cols as $i => $c) {
            $ws->setCellValueExplicit($L($i + 1) . $hr, $c['label'], DataType::TYPE_STRING);
        }
        $ws->getStyle("A{$hr}:{$last}{$hr}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3864']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1F3864']]],
        ]);
        $ws->getRowDimension($hr)->setRowHeight(30);

        // ---- Isi ----
        $rows  = $report['rows'];
        $first = $hr + 1;
        $count = count($rows);

        if ($count === 0) {
            $ws->setCellValue("A{$first}", 'Tidak ada data untuk kombinasi filter ini.');
            $ws->mergeCells("A{$first}:{$last}{$first}");
            $ws->getStyle("A{$first}")->applyFromArray([
                'font'      => ['italic' => true, 'color' => ['rgb' => '7F7F7F']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            $ws->getRowDimension($first)->setRowHeight(28);
            $lastData = $first;
        } else {
            $lastData = $first + $count - 1;

            foreach ($rows as $ri => $row) {
                $rowNo = $first + $ri;
                foreach ($cols as $ci => $c) {
                    $v = $row[$ci] ?? null;
                    if ($v === null || $v === '') {
                        continue;
                    }
                    $coord = $L($ci + 1) . $rowNo;
                    switch ($c['type']) {
                        case 'qty':
                        case 'money':
                            $ws->setCellValue($coord, (float) $v);
                            break;
                        case 'datetime':
                            $ws->setCellValue($coord, XlDate::PHPToExcel(Carbon::parse($v)));
                            break;
                        default:
                            $ws->setCellValueExplicit($coord, (string) $v, DataType::TYPE_STRING);
                    }
                }
            }

            // Style per kolom (satu kali per rentang, bukan per sel -> cepat untuk ribuan baris)
            $fullRange = "A{$first}:{$last}{$lastData}";
            $ws->getStyle($fullRange)->applyFromArray([
                'font'      => ['size' => 10],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D5DD']]],
            ]);

            foreach ($cols as $ci => $c) {
                $range = $L($ci + 1) . $first . ':' . $L($ci + 1) . $lastData;
                $style = $ws->getStyle($range);
                switch ($c['type']) {
                    case 'qty':
                    case 'money':
                        $style->getNumberFormat()->setFormatCode('#,##0.00');
                        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                        break;
                    case 'datetime':
                        $style->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
                        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        break;
                    case 'badge':
                        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        break;
                    default:
                        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                }
            }

            // Warna badge via conditional formatting (status/jenis)
            foreach ($cols as $ci => $c) {
                if ($c['type'] !== 'badge') {
                    continue;
                }
                $range = $L($ci + 1) . $first . ':' . $L($ci + 1) . $lastData;
                $conds = [];
                foreach (self::BADGE_COLORS as $text => [$bg, $fg]) {
                    $cond = new Conditional();
                    $cond->setConditionType(Conditional::CONDITION_CELLIS)
                        ->setOperatorType(Conditional::OPERATOR_EQUAL)
                        ->addCondition('"' . $text . '"');
                    $cond->getStyle()->getFont()->setBold(true)->getColor()->setRGB($fg);
                    $cond->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)->getEndColor()->setRGB($bg);
                    $conds[] = $cond;
                }
                $ws->getStyle($range)->setConditionalStyles($conds);
            }

            // Baris pertama tebal (Saldo Awal pada Kartu Stok)
            if (!empty($report['bold_first_row'])) {
                $ws->getStyle("A{$first}:{$last}{$first}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF8E1']],
                ]);
            }

            // Filter di header tabel (baris TOTAL berada di luar rentang filter)
            $ws->setAutoFilter("A{$hr}:{$last}{$lastData}");
        }

        // ---- Baris TOTAL ----
        if ($count > 0 && !empty($report['totals'])) {
            $tr = $lastData + 1;
            $ws->setCellValue("A{$tr}", 'TOTAL');

            foreach ($report['totals'] as $ci => $value) {
                $colL  = $L($ci + 1);
                $coord = $colL . $tr;
                if (in_array($ci, $report['sum_cols'] ?? [], true)) {
                    // SUBTOTAL(109) ikut berubah saat user memakai AutoFilter di Excel
                    $ws->setCellValue($coord, "=SUBTOTAL(109,{$colL}{$first}:{$colL}{$lastData})");
                } else {
                    $ws->setCellValue($coord, (float) $value);
                }
                $ws->getStyle($coord)->getNumberFormat()->setFormatCode('#,##0.00');
                $ws->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            }

            $ws->getStyle("A{$tr}:{$last}{$tr}")->applyFromArray([
                'font'    => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '1F3864']],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9E1F2']],
                'borders' => [
                    'top'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1F3864']],
                    'bottom' => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => '1F3864']],
                ],
            ]);
            $ws->getRowDimension($tr)->setRowHeight(22);
            $ws->getStyle("A{$tr}:{$last}{$tr}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        }

        // ---- Freeze, cetak ----
        $ws->freezePane('A' . ($hr + 1));

        $page = $ws->getPageSetup();
        $page->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
        $page->setPaperSize(PageSetup::PAPERSIZE_A4);
        $page->setFitToPage(true);
        $page->setFitToWidth(1);
        $page->setFitToHeight(0);
        $page->setRowsToRepeatAtTopByStartAndEnd($hr, $hr);
        $ws->getPageMargins()->setTop(0.5)->setBottom(0.6)->setLeft(0.4)->setRight(0.4);
        $ws->getHeaderFooter()->setOddFooter('&L&8' . $report['title'] . '&R&8Halaman &P dari &N');
        $ws->setSelectedCell('A1');

        return $ss;
    }

    // =====================================================================
    //  RIWAYAT
    // =====================================================================

    private function saveHistory(array $f, string $filename): void
    {
        LaporanHistory::create([
            'id_user'        => auth()->id(),
            'id_bisnis_unit' => $f['id_bu'] ?: ($f['access']['id_bisnis_unit'] ?? null),
            'jenis'          => $f['jenis'],
            'id_area'        => $f['id_area'],
            'id_barang'      => $f['id_barang'],
            'tanggal_awal'   => $f['tanggal_awal'],
            'tanggal_akhir'  => $f['tanggal_akhir'],
            'nama_file'      => $filename,
        ]);
    }

    // =====================================================================
    //  HELPER FORMAT
    // =====================================================================

    private function areaLabel($area): string
    {
        if (!$area) {
            return '-';
        }
        return $area->nama_area . ' (' . ($area->bisnisUnit->nama_bisnis_unit ?? '-') . ')';
    }

    private function jenisLabel(?string $jenis): string
    {
        return [
            'masuk'    => 'Masuk',
            'keluar'   => 'Keluar',
            'transfer' => 'Transfer',
            'opname'   => 'Opname',
        ][$jenis] ?? ucfirst((string) $jenis);
    }

    /** Format tampilan untuk preview (format angka Indonesia: 1.234,50). */
    private function display(string $type, $v): string
    {
        if ($v === null || $v === '') {
            return '';
        }
        return match ($type) {
            'int'      => number_format((float) $v, 0, ',', '.'),
            'qty',
            'money'    => number_format((float) $v, 2, ',', '.'),
            'datetime' => Carbon::parse($v)->format('d/m/Y H:i'),
            default    => (string) $v,
        };
    }

    private function getStatusTransaksiLabel(?string $status): string
    {
        return [
            'aktif'      => 'Aktif',
            'dibatalkan' => 'Dibatalkan',
            'koreksi'    => 'Koreksi',
        ][$status] ?? ucfirst($status ?? 'aktif');
    }

    private function getStatusLabel(?string $status): string
    {
        return [
            'pending_l1'    => 'Menunggu L1',
            'pending_admin' => 'Menunggu Admin',
            'disetujui'     => 'Disetujui',
            'ditolak'       => 'Ditolak',
        ][$status] ?? ucfirst((string) $status);
    }
}