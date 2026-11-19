<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use App\Models\BisnisUnit;
use App\Models\StockCtl\AreaKerja;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockDashboardController extends Controller
{
    /** Data yang dimuat ke dashboard: N bulan terakhir (termasuk bulan ini). */
    private const MAX_BULAN = 24;

    /**
     * Semua data dimuat sekali bersama halaman (tanpa AJAX), lalu difilter & diagregasi
     * di browser. Hasilnya: filter terasa instan dan tidak ada request tambahan yang
     * bisa ditolak middleware auth.
     */
    public function index()
    {
        $access   = session('stock_ctl_access');
        $isSuper  = !empty($access['is_super']);
        $lockedBu = $isSuper ? null : (int) $access['id_bisnis_unit'];

        $batasAwal = Carbon::now()->subMonths(self::MAX_BULAN - 1)->startOfMonth()->toDateString();

        // ---- Master filter ----
        $bisnisUnits = $isSuper
            ? BisnisUnit::orderBy('nama_bisnis_unit')->get()
            : BisnisUnit::where('id_bisnis_unit', $access['id_bisnis_unit'])->get();

        $areas = AreaKerja::with('bisnisUnit')
            ->when(!$isSuper, fn ($q) => $q->where('id_bisnis_unit', $access['id_bisnis_unit']))
            ->orderBy('nama_area')
            ->get()
            ->map(fn ($a) => [
                'id'      => (int) $a->id_area_kerja,
                'nama'    => $a->nama_area,
                'bu'      => (int) $a->id_bisnis_unit,
                'bu_nama' => $a->bisnisUnit->nama_bisnis_unit ?? '-',
            ])->values();

        $areaIdsScope = $isSuper ? null : $areas->pluck('id')->all();

        // ---- Permintaan: satu baris ringkas per permintaan ----
        // Unit = nama divisi pemohon (teks sebelum " (" ), sama dengan kolom Unit di Laporan.
        $unitList  = [];
        $unitIndex = [];
        $unitIdx = function ($raw) use (&$unitList, &$unitIndex) {
            $nama = trim(explode(' (', (string) $raw)[0]);
            if ($nama === '') {
                $nama = '(Tanpa Unit)';
            }
            if (!isset($unitIndex[$nama])) {
                $unitIndex[$nama] = count($unitList);
                $unitList[]       = $nama;
            }
            return $unitIndex[$nama];
        };

        // kode status: 0 disetujui, 1 ditolak, 2 dalam proses, 3 dibatalkan
        $kodeStatus = [
            'disetujui' => 0, 'ditolak' => 1,
            'pending_l1' => 2, 'pending_admin' => 2,
            'dibatalkan' => 3,
        ];

        $q = DB::table('stock_ctl_permintaan as p')
            ->join('stock_ctl_area_kerja as a', 'a.id_area_kerja', '=', 'p.id_area_kerja')
            ->leftJoin('stock_ctl_barang as b', 'b.id_barang', '=', 'p.id_barang')
            ->leftJoin('users as u', 'u.id', '=', 'p.id_user_pemohon')
            ->whereDate('p.tanggal_permintaan', '>=', $batasAwal)
            ->orderBy('p.tanggal_permintaan')
            ->orderBy('p.id_permintaan');

        if (!$isSuper) {
            // sama dengan Laporan: hanya permintaan dari pemohon di Bisnis Unit akun ini
            $q->whereExists(function ($s) use ($access) {
                $s->select(DB::raw(1))
                    ->from('stock_ctl_user_profil')
                    ->whereColumn('stock_ctl_user_profil.id_user', 'p.id_user_pemohon')
                    ->where('stock_ctl_user_profil.id_bisnis_unit', $access['id_bisnis_unit']);
            });
        }

        $hasil = $q->selectRaw(
            'DATE(p.tanggal_permintaan) as tgl,
             a.id_bisnis_unit as bu,
             p.id_area_kerja as area,
             (SELECT pr.unit FROM stock_ctl_user_profil pr WHERE pr.id_user = p.id_user_pemohon LIMIT 1) as unit,
             p.id_barang as barang,
             p.status as status,
             ROUND(p.jumlah * COALESCE(b.harga, 0)) as nilai,
             p.jumlah as qty,
             p.id_user_pemohon as pemohon,
             u.name as pemohon_nama'
        )->get();

        $rows      = [];
        $barangIds = [];
        $pemohonNama = [];
        foreach ($hasil as $r) {
            if (!isset($kodeStatus[$r->status])) {
                continue;
            }
            // urutan: tanggal, bu, area, unitIdx, barang, status, nilai, qty, pemohon
            $rows[] = [
                $r->tgl,
                (int) $r->bu,
                (int) $r->area,
                $unitIdx($r->unit),
                (int) $r->barang,
                $kodeStatus[$r->status],
                (int) $r->nilai,
                (int) $r->qty,   // r[7] = jumlah barang (ganti ke (float) kalau jumlah bisa desimal)
                (int) $r->pemohon, // r[8] = id user pemohon (nama ada di payload 'pemohon')
            ];
            $barangIds[(int) $r->barang] = true;
            $pemohonNama[(int) $r->pemohon] = $r->pemohon_nama ?: ('User #' . (int) $r->pemohon);
        }

        $barangList = [];
        if ($barangIds) {
            $barangList = DB::table('stock_ctl_barang')
                ->whereIn('id_barang', array_keys($barangIds))
                ->orderBy('nama_barang')
                ->get(['id_barang', 'nama_barang'])
                ->map(fn ($b) => [(int) $b->id_barang, $b->nama_barang])
                ->values()
                ->all();
        }

        // Daftar pemohon [id, nama] untuk panel data & grafik pemohon teratas
        $pemohonList = collect($pemohonNama)->map(fn ($nama, $id) => [(int) $id, $nama])->sortBy(fn ($x) => mb_strtolower($x[1]))->values()->all();

        // ---- Transaksi aktif, diringkas per hari/jenis/area ----
        // masuk -> area tujuan, keluar -> area asal (sama dengan Laporan)
        $trxQ = DB::table('stock_ctl_transaksi as t')
            ->where('t.status', 'aktif')
            ->whereIn('t.jenis', ['masuk', 'keluar'])
            ->whereDate('t.tanggal', '>=', $batasAwal);

        if ($areaIdsScope !== null) {
            $ids = $areaIdsScope ?: [0];
            $trxQ->where(function ($w) use ($ids) {
                $w->where(fn ($x) => $x->where('t.jenis', 'masuk')->whereIn('t.id_area_tujuan', $ids))
                  ->orWhere(fn ($x) => $x->where('t.jenis', 'keluar')->whereIn('t.id_area_asal', $ids));
            });
        }

        // Tiap baris: [tanggal, jenis (0 masuk / 1 keluar), area, jumlah transaksi, jumlah item]
        // CATATAN: t.jumlah = kolom jumlah barang di stock_ctl_transaksi.
        // Kalau namanya berbeda di tabel Anda, sesuaikan (cek: DESCRIBE stock_ctl_transaksi;)
        $trx = $trxQ->selectRaw(
            "DATE(t.tanggal) as tgl, t.jenis as jenis,
             IF(t.jenis = 'masuk', t.id_area_tujuan, t.id_area_asal) as area,
             COUNT(*) as n,
             COALESCE(SUM(t.jumlah), 0) as qty"
        )->groupBy('tgl', 'jenis', 'area')->get()
            ->map(fn ($r) => [
                $r->tgl,
                $r->jenis === 'masuk' ? 0 : 1,
                (int) $r->area,
                (int) $r->n,
                (int) $r->qty,
            ])
            ->values()->all();

        // ---- Kondisi stok saat ini, diringkas per area ----
        $stokQ = DB::table('stock_ctl_stok as s');
        if ($areaIdsScope !== null) {
            $stokQ->whereIn('s.id_area_kerja', $areaIdsScope ?: [0]);
        }
        $stok = $stokQ->selectRaw(
            'COALESCE(s.id_area_kerja, 0) as area,
             COALESCE(SUM(s.jumlah > COALESCE(s.stok_minimum, 0)), 0) as aman,
             COALESCE(SUM(s.jumlah > 0 AND s.jumlah <= COALESCE(s.stok_minimum, 0)), 0) as menipis,
             COALESCE(SUM(s.jumlah <= 0), 0) as habis'
        )->groupBy('s.id_area_kerja')->get()
            ->map(fn ($r) => [(int) $r->area, (int) $r->aman, (int) $r->menipis, (int) $r->habis])
            ->values()->all();

        $payload = [
            'batasAwal' => $batasAwal,
            'hariIni'   => Carbon::now()->toDateString(),
            'lockedBu'  => $lockedBu,
            'bus'       => $bisnisUnits->map(fn ($b) => ['id' => (int) $b->id_bisnis_unit, 'nama' => $b->nama_bisnis_unit])->values(),
            'areas'     => $areas,
            'units'     => $unitList,
            'barang'    => $barangList,
            'pemohon'   => $pemohonList,
            'rows'      => $rows,
            'trx'       => $trx,
            'stok'      => $stok,
        ];

        return view('stock-ctl.dashboard', compact('payload', 'access', 'isSuper'));
    }
}