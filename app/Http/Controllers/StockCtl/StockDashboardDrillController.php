<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use App\Models\BisnisUnit;
use App\Models\StockCtl\AreaKerja;
use App\Models\StockCtl\Permintaan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StockDashboardDrillController extends Controller
{
    /** Data yang dimuat ke dashboard: N bulan terakhir (termasuk bulan ini). */
    private const MAX_BULAN = 24;

    /**
     * Dashboard Drill-down. Sumber data & filter SAMA dengan StockDashboardController (dashboard lama):
     * semua data dimuat sekali bersama halaman lalu difilter & diagregasi di browser.
     * Bedanya: tiap baris membawa alasan tolak (khusus status Ditolak) dan tidak memuat data stok.
     * Dashboard lama tidak diubah.
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

        // kode status: 0 disetujui, 1 ditolak, 2 pending L1, 3 dibatalkan, 4 pending admin
        $kodeStatus = [
            'disetujui' => 0, 'ditolak' => 1,
            'pending_l1' => 2, 'pending_admin' => 4,
            'dibatalkan' => 3,
        ];

        // Harga: snapshot saat disetujui (kolom harga_satuan) bila ada, kalau tidak harga barang saat ini.
        $hargaExpr = Permintaan::adaKolomHarga()
            ? 'COALESCE(p.harga_satuan, b.harga, 0)'
            : 'COALESCE(b.harga, 0)';

        // Barang & jumlah EFEKTIF: bila admin mengganti barang (substitusi), yang dihitung adalah
        // barang pengganti & jumlah pengganti (yang benar-benar dikeluarkan), bukan barang asli.
        $q = DB::table('stock_ctl_permintaan as p')
            ->join('stock_ctl_area_kerja as a', 'a.id_area_kerja', '=', 'p.id_area_kerja')
            ->leftJoin('stock_ctl_substitusi as sb', 'sb.id_permintaan', '=', 'p.id_permintaan')
            ->leftJoin('stock_ctl_barang as b', function ($j) {
                $j->whereRaw('b.id_barang = COALESCE(sb.id_barang_pengganti, p.id_barang)');
            })
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
            "DATE(p.tanggal_permintaan) as tgl,
             a.id_bisnis_unit as bu,
             p.id_area_kerja as area,
             (SELECT pr.unit FROM stock_ctl_user_profil pr WHERE pr.id_user = p.id_user_pemohon LIMIT 1) as unit,
             COALESCE(sb.id_barang_pengganti, p.id_barang) as barang,
             p.status as status,
             ROUND(COALESCE(sb.jumlah_pengganti, p.jumlah) * {$hargaExpr}) as nilai,
             COALESCE(sb.jumlah_pengganti, p.jumlah) as qty,
             p.id_user_pemohon as pemohon,
             u.name as pemohon_nama,
             p.alasan_tolak as alasan"
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
                (float) $r->qty,  // r[7] = jumlah barang (bisa desimal)
                (int) $r->pemohon, // r[8] = id user pemohon (nama ada di payload 'pemohon')
                // r[9] = alasan tolak (hanya untuk status Ditolak; selain itu kosong)
                $r->status === 'ditolak' ? trim((string) ($r->alasan ?? '')) : '',
            ];
            $barangIds[(int) $r->barang] = true;
            $pemohonNama[(int) $r->pemohon] = $r->pemohon_nama ?: ('User #' . (int) $r->pemohon);
        }

        // Permintaan dibatasi menurut BU pemohon (sama seperti Laporan & Approval Admin), sehingga bisa
        // ada permintaan ke area milik BU lain. Sertakan area/BU itu supaya namanya tampil di dashboard.
        $areaDikenal = $areas->pluck('id')->all();
        $areaExtra   = $hasil->pluck('area')->map(fn ($v) => (int) $v)->unique()->diff($areaDikenal)->values()->all();
        if ($areaExtra) {
            $extraAreas = AreaKerja::with('bisnisUnit')->whereIn('id_area_kerja', $areaExtra)->get();
            $areas = $areas->concat($extraAreas->map(fn ($a) => [
                'id'      => (int) $a->id_area_kerja,
                'nama'    => $a->nama_area,
                'bu'      => (int) $a->id_bisnis_unit,
                'bu_nama' => $a->bisnisUnit->nama_bisnis_unit ?? '-',
            ]))->values();
            $buDikenal = $bisnisUnits->pluck('id_bisnis_unit')->map(fn ($v) => (int) $v)->all();
            $buExtra   = BisnisUnit::whereIn('id_bisnis_unit', $extraAreas->pluck('id_bisnis_unit')->map(fn ($v) => (int) $v)->unique()->diff($buDikenal)->values()->all())->get();
            $bisnisUnits = $bisnisUnits->concat($buExtra)->values();
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
        ];

        return view('stock-ctl.dashboard-drill', compact('payload', 'access', 'isSuper'));
    }
}