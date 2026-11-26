<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use App\Models\BisnisUnit;
use App\Models\StockCtl\Permintaan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard drill-down (baru): Barang -> Bisnis Unit -> Dept/Area -> Unit -> User -> Detail permintaan.
 * Dashboard lama (StockDashboardController) tidak diubah.
 */
class StockDashboardDrillController extends Controller
{
    private const LEVELS = ['barang', 'bu', 'area', 'unit', 'user', 'detail'];

    // barang & jumlah EFEKTIF: bila ada substitusi, yang dihitung barang/jumlah pengganti (sama dengan dashboard lama)
    private const BARANG_EXPR = 'COALESCE(sb.id_barang_pengganti, p.id_barang)';
    private const QTY_EXPR    = 'COALESCE(sb.jumlah_pengganti, p.jumlah)';
    // Unit = nama divisi pemohon (teks sebelum " (") - sama dengan dashboard lama & Laporan
    private const UNIT_EXPR   = "COALESCE(NULLIF(TRIM(SUBSTRING_INDEX((SELECT pr.unit FROM stock_ctl_user_profil pr WHERE pr.id_user = p.id_user_pemohon LIMIT 1), ' (', 1)), ''), '(Tanpa Unit)')";

    private const STATUS = [
        'semua'      => null,
        'disetujui'  => ['disetujui'],
        'menunggu'   => ['pending_l1', 'pending_admin'],
        'ditolak'    => ['ditolak'],
        'dibatalkan' => ['dibatalkan'],
    ];

    // kode status (sama dengan dashboard lama): 0 disetujui, 1 ditolak, 2 pending L1, 3 dibatalkan, 4 pending admin
    private const STATUS_KODE = [
        'disetujui' => 0, 'ditolak' => 1, 'pending_l1' => 2, 'dibatalkan' => 3, 'pending_admin' => 4,
    ];

    public function index()
    {
        $access  = session('stock_ctl_access');
        $isSuper = !empty($access['is_super']);

        $tahunIni = (int) date('Y');
        $min      = DB::table('stock_ctl_permintaan')->min('tanggal_permintaan');
        $tahunMin = $min ? min((int) substr($min, 0, 4), $tahunIni) : $tahunIni;
        $tahunList = range($tahunIni, $tahunMin);

        return view('stock-ctl.dashboard-drill', compact('access', 'isSuper', 'tahunList', 'tahunIni'));
    }

    public function data(Request $request)
    {
        $data = $request->validate([
            'level'  => 'required|in:' . implode(',', self::LEVELS),
            'tahun'  => 'required|integer|min:2000|max:2100',
            'bulan'  => 'nullable|integer|min:0|max:12',
            'status' => 'nullable|in:' . implode(',', array_keys(self::STATUS)),
        ]);

        $access  = session('stock_ctl_access');
        $isSuper = !empty($access['is_super']);
        $level   = $data['level'];

        $q = $this->base($request, $data, $isSuper, $access);

        if ($level === 'detail') {
            $tot = (clone $q)->selectRaw('COUNT(*) as jml, COALESCE(SUM(' . self::QTY_EXPR . '), 0) as qty')->first();
            $items = $q->selectRaw('p.id_permintaan as k, DATE(p.tanggal_permintaan) as tgl, ' . self::QTY_EXPR . ' as qty, p.status as status')
                ->orderByDesc('p.tanggal_permintaan')->orderByDesc('p.id_permintaan')
                ->limit(200)->get()
                ->map(fn ($r) => ['k' => (int) $r->k, 'tgl' => $r->tgl, 'qty' => (float) $r->qty, 'status' => $r->status])
                ->values();

            return response()->json([
                'level' => $level,
                'items' => $items,
                'total' => ['permintaan' => (int) $tot->jml, 'qty' => (float) $tot->qty],
                'cut'   => (int) $tot->jml > 200,
            ]);
        }

        [$kExpr, $labelExpr] = match ($level) {
            'barang' => [self::BARANG_EXPR, 'MAX(b.nama_barang)'],
            'bu'     => ['a.id_bisnis_unit', 'MAX(a.id_bisnis_unit)'],
            'area'   => ['p.id_area_kerja', 'MAX(a.nama_area)'],
            'unit'   => [self::UNIT_EXPR, self::UNIT_EXPR],
            'user'   => ['p.id_user_pemohon', 'MAX(u.name)'],
        };

        $rows = $q->selectRaw("{$kExpr} as k, {$labelExpr} as label, COUNT(*) as jml, COALESCE(SUM(" . self::QTY_EXPR . "), 0) as qty")
            ->groupByRaw($kExpr)
            ->orderByDesc('qty')->orderByDesc('jml')
            ->get();

        $buNama = [];
        if ($level === 'bu' && $rows->isNotEmpty()) {
            $buNama = BisnisUnit::whereIn('id_bisnis_unit', $rows->pluck('k')->all())
                ->pluck('nama_bisnis_unit', 'id_bisnis_unit')->all();
        }

        $items = $rows->map(function ($r) use ($level, $buNama) {
            $label = $r->label;
            if ($level === 'bu') {
                $label = $buNama[$r->k] ?? ('BU #' . $r->k);
            } elseif ($level === 'user' && ($label === null || $label === '')) {
                $label = 'User #' . $r->k;
            } elseif ($label === null || $label === '') {
                $label = '-';
            }
            return [
                'k'          => $level === 'unit' ? (string) $r->k : (int) $r->k,
                'label'      => $label,
                'permintaan' => (int) $r->jml,
                'qty'        => (float) $r->qty,
            ];
        })->values();

        return response()->json([
            'level' => $level,
            'items' => $items,
            'total' => [
                'permintaan' => (int) $items->sum('permintaan'),
                'qty'        => (float) $items->sum('qty'),
            ],
        ]);
    }

    /** Data grafik: permintaan per bulan (atau per hari bila satu bulan dipilih), ditumpuk menurut status. */
    public function chart(Request $request)
    {
        $data = $request->validate([
            'tahun'  => 'required|integer|min:2000|max:2100',
            'bulan'  => 'nullable|integer|min:0|max:12',
            'status' => 'nullable|in:' . implode(',', array_keys(self::STATUS)),
        ]);

        $access  = session('stock_ctl_access');
        $isSuper = !empty($access['is_super']);
        $tahun   = (int) $data['tahun'];
        $bulan   = (int) ($data['bulan'] ?? 0);

        $gran = $bulan ? 'day' : 'month';
        $fmt  = $bulan ? '%Y-%m-%d' : '%Y-%m';

        $keys = [];
        if ($bulan) {
            $awal = Carbon::create($tahun, $bulan, 1);
            for ($d = 1; $d <= $awal->daysInMonth; $d++) {
                $keys[] = $awal->format('Y-m-') . str_pad((string) $d, 2, '0', STR_PAD_LEFT);
            }
        } else {
            for ($m = 1; $m <= 12; $m++) {
                $keys[] = $tahun . '-' . str_pad((string) $m, 2, '0', STR_PAD_LEFT);
            }
        }
        $idx = array_flip($keys);

        $q = $this->base($request, $data, $isSuper, $access);
        $rows = $q->selectRaw("DATE_FORMAT(p.tanggal_permintaan, '{$fmt}') as bk, p.status as st, COUNT(*) as jml, COALESCE(SUM(" . self::QTY_EXPR . "), 0) as qty")
            ->groupByRaw("DATE_FORMAT(p.tanggal_permintaan, '{$fmt}'), p.status")
            ->get();

        $nol = array_fill(0, count($keys), 0);
        $per = [];
        $tot = [];
        foreach (self::STATUS_KODE as $kode) {
            $per[$kode] = ['permintaan' => $nol, 'qty' => $nol];
            $tot[$kode] = ['permintaan' => 0, 'qty' => 0];
        }
        foreach ($rows as $r) {
            if (!isset(self::STATUS_KODE[$r->st]) || !isset($idx[$r->bk])) {
                continue;
            }
            $k = self::STATUS_KODE[$r->st];
            $i = $idx[$r->bk];
            $per[$k]['permintaan'][$i] = (int) $r->jml;
            $per[$k]['qty'][$i]        = (float) $r->qty;
            $tot[$k]['permintaan']    += (int) $r->jml;
            $tot[$k]['qty']           += (float) $r->qty;
        }

        return response()->json([
            'gran'   => $gran,
            'keys'   => $keys,
            'per'    => array_values($per),   // indeks = kode status
            'total'  => array_values($tot),
        ]);
    }

    /** Daftar permintaan untuk panel kanan (klik batang / status di grafik). Maks. 500 baris terbaru. */
    public function rows(Request $request)
    {
        $data = $request->validate([
            'tahun'  => 'required|integer|min:2000|max:2100',
            'bulan'  => 'nullable|integer|min:0|max:12',
            'status' => 'nullable|in:' . implode(',', array_keys(self::STATUS)),
            'bucket' => ['nullable', 'regex:/^\d{4}-\d{2}(-\d{2})?$/'],
            'st'     => 'nullable|in:' . implode(',', array_keys(self::STATUS_KODE)),
        ]);

        $access  = session('stock_ctl_access');
        $isSuper = !empty($access['is_super']);

        $harga = Permintaan::adaKolomHarga() ? 'COALESCE(p.harga_satuan, b.harga, 0)' : 'COALESCE(b.harga, 0)';
        $nilai = 'ROUND(' . self::QTY_EXPR . ' * ' . $harga . ')';

        $q = $this->base($request, $data, $isSuper, $access);

        $tot = (clone $q)->selectRaw('COUNT(*) as jml, COALESCE(SUM(' . self::QTY_EXPR . '), 0) as qty, COALESCE(SUM(' . $nilai . '), 0) as nilai')->first();

        $hasil = $q->selectRaw(
            'p.id_permintaan as id, DATE(p.tanggal_permintaan) as tgl, u.name as pemohon, b.nama_barang as barang, '
            . self::UNIT_EXPR . ' as unit_nama, a.nama_area as area, a.id_bisnis_unit as bu, p.status as status, '
            . self::QTY_EXPR . ' as qty, ' . $nilai . ' as nilai'
        )->orderByDesc('p.tanggal_permintaan')->orderByDesc('p.id_permintaan')->limit(500)->get();

        $buNama = BisnisUnit::whereIn('id_bisnis_unit', $hasil->pluck('bu')->unique()->all())
            ->pluck('nama_bisnis_unit', 'id_bisnis_unit')->all();

        $items = $hasil->map(fn ($r) => [
            'id'      => (int) $r->id,
            'tgl'     => $r->tgl,
            'pemohon' => $r->pemohon ?: '-',
            'barang'  => $r->barang ?: ('#' . $r->id),
            'sub'     => ($r->unit_nama ?: '-') . ' › ' . ($r->area ?: '-') . ' › ' . ($buNama[$r->bu] ?? '-'),
            'status'  => $r->status,
            'qty'     => (float) $r->qty,
            'nilai'   => (int) $r->nilai,
        ])->values();

        return response()->json([
            'items' => $items,
            'total' => ['permintaan' => (int) $tot->jml, 'qty' => (float) $tot->qty, 'nilai' => (int) $tot->nilai],
            'cut'   => (int) $tot->jml > 500,
        ]);
    }

    /** Query dasar + filter periode, status, scope BU, dan pilihan drill-down sebelumnya. */
    private function base(Request $r, array $data, bool $isSuper, $access)
    {
        $tahun = (int) $data['tahun'];
        $bulan = (int) ($data['bulan'] ?? 0);

        $awal  = $bulan ? Carbon::create($tahun, $bulan, 1)->startOfMonth() : Carbon::create($tahun, 1, 1)->startOfYear();
        $akhir = $bulan ? $awal->copy()->endOfMonth() : $awal->copy()->endOfYear();

        $q = DB::table('stock_ctl_permintaan as p')
            ->join('stock_ctl_area_kerja as a', 'a.id_area_kerja', '=', 'p.id_area_kerja')
            ->leftJoin('stock_ctl_substitusi as sb', 'sb.id_permintaan', '=', 'p.id_permintaan')
            ->leftJoin('stock_ctl_barang as b', function ($j) {
                $j->whereRaw('b.id_barang = ' . self::BARANG_EXPR);
            })
            ->leftJoin('users as u', 'u.id', '=', 'p.id_user_pemohon')
            ->whereDate('p.tanggal_permintaan', '>=', $awal->toDateString())
            ->whereDate('p.tanggal_permintaan', '<=', $akhir->toDateString());

        $status = self::STATUS[$data['status'] ?? 'semua'] ?? null;
        if ($status) {
            $q->whereIn('p.status', $status);
        }

        if (!$isSuper) {
            // sama dengan dashboard lama & Laporan: hanya pemohon di Bisnis Unit akun ini
            $q->whereExists(function ($s) use ($access) {
                $s->select(DB::raw(1))
                    ->from('stock_ctl_user_profil')
                    ->whereColumn('stock_ctl_user_profil.id_user', 'p.id_user_pemohon')
                    ->where('stock_ctl_user_profil.id_bisnis_unit', $access['id_bisnis_unit']);
            });
        }

        $has = fn ($k) => $r->has($k) && $r->input($k) !== null && $r->input($k) !== '';
        if ($has('barang')) $q->whereRaw(self::BARANG_EXPR . ' = ?', [(int) $r->input('barang')]);
        if ($has('bu'))     $q->where('a.id_bisnis_unit', (int) $r->input('bu'));
        if ($has('area'))   $q->where('p.id_area_kerja', (int) $r->input('area'));
        if ($has('unit'))   $q->whereRaw(self::UNIT_EXPR . ' = ?', [(string) $r->input('unit')]);
        if ($has('user'))   $q->where('p.id_user_pemohon', (int) $r->input('user'));

        // klik grafik: batasi ke satu bulan/hari dan/atau satu status
        if ($has('bucket')) {
            $b = (string) $r->input('bucket');
            if (preg_match('/^\d{4}-\d{2}$/', $b)) {
                $q->whereRaw("DATE_FORMAT(p.tanggal_permintaan, '%Y-%m') = ?", [$b]);
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $b)) {
                $q->whereDate('p.tanggal_permintaan', $b);
            }
        }
        if ($has('st') && isset(self::STATUS_KODE[$r->input('st')])) {
            $q->where('p.status', (string) $r->input('st'));
        }

        return $q;
    }
}