<?php

namespace App\Http\Controllers\Messenger;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * =====================================================================
 *  MESSENGER DASHBOARD CONTROLLER (ADMIN)
 * =====================================================================
 *  File terpisah dari MessengerController agar controller transaksi
 *  (pengiriman/kurir) tidak tercampur dengan logic dashboard & grafik.
 *
 *  - index()      : dashboard (filter Tampilan/Bulan/Tahun/Status/Kurir,
 *                   grafik laporan bulanan/harian + total per kurir;
 *                   pilih 2-6 kurir = mode PERBANDINGAN)
 *  - dataKurir()  : JSON drilldown saat batang kurir diklik
 *  - dataBulan()  : JSON drilldown saat batang bulan/hari diklik
 *
 *  Otorisasi lewat middleware route 'messenger.access:dashboard_messenger'.
 * =====================================================================
 */
class MessengerDashboardController extends Controller
{
    private const STATUS_LIST = [
        'Belum Terkirim',
        'Proses Pengiriman',
        'Dokumen Belum Tersedia',
        'Terkirim',
        'Ditolak',
        'Batal',
    ];

    /* =====================================================
     |  DASHBOARD (INDEX)
     ===================================================== */
    public function index(Request $request)
    {
        // ---------- FILTER ----------
        $view = $request->input('view', 'year') === 'month' ? 'month' : 'year';

        $minDate = DB::table('tb_transaksi')->min('created_at');
        $minCarbon = $minDate ? Carbon::parse($minDate) : now();

        // daftar tahun (terbaru di atas)
        $years = range((int) now()->year, (int) $minCarbon->year);
        $year  = (int) $request->input('year', now()->year);
        if (!in_array($year, $years, true)) {
            $year = (int) now()->year;
        }

        // daftar bulan (maks 36 bulan terakhir, terbaru di atas)
        $months = [];
        $cursor = now()->startOfMonth();
        $minMonth = $minCarbon->copy()->startOfMonth();
        while ($cursor->gte($minMonth) && count($months) < 36) {
            $months[$cursor->format('Y-m')] = $cursor->isoFormat('MMMM Y');
            $cursor = $cursor->copy()->subMonth();
        }
        $month = $request->input('month', now()->format('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }
        if (!isset($months[$month])) {
            $months[$month] = Carbon::parse($month . '-01')->isoFormat('MMMM Y');
        }

        $status = $request->input('status');
        if (!in_array($status, self::STATUS_LIST, true)) {
            $status = null;
        }

        // Daftar kurir untuk dropdown (kurir = pelanggan yang pernah tercatat di tb_transaksi.kurir)
        $kurirList = DB::table('tb_transaksi as tk')
            ->join('tb_pelanggan as pk', 'pk.id_pelanggan', '=', 'tk.kurir')
            ->where('tk.kurir', '>', 0)
            ->select('pk.id_pelanggan', 'pk.nama_pelanggan')
            ->distinct()
            ->orderBy('pk.nama_pelanggan')
            ->get();

        // Filter kurir: boleh BANYAK (kurir[]=1&kurir[]=2). Kosong / 'all' = semua kurir.
        $kurirReq = $request->input('kurir');
        $reqIds   = array_map('strval', array_filter(is_array($kurirReq) ? $kurirReq : [$kurirReq], 'is_scalar'));
        $kurirSel = $kurirList
            ->filter(fn ($k) => in_array((string) $k->id_pelanggan, $reqIds, true))
            ->pluck('id_pelanggan')
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        // Mode perbandingan: 2 s/d 6 kurir dipilih
        $compare = count($kurirSel) >= 2 && count($kurirSel) <= 6;

        // ---------- RENTANG PERIODE ----------
        if ($view === 'year') {
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end   = $start->copy()->endOfYear();
            $periodLabel = 'Tahun ' . $year;
        } else {
            $start = Carbon::parse($month . '-01')->startOfMonth();
            $end   = $start->copy()->endOfMonth();
            $periodLabel = $start->isoFormat('MMMM Y');
        }

        // ---------- RINGKASAN PER STATUS (dalam periode) ----------
        $statusCounts = DB::table('tb_transaksi')
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$start, $end])
            ->when($kurirSel, fn ($q) => $q->whereIn('kurir', $kurirSel))
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = [];
        foreach (self::STATUS_LIST as $s) {
            $summary[$s] = (int) ($statusCounts[$s] ?? 0);
        }
        $totalKeseluruhan = (int) $statusCounts->sum();

        // ---------- GRAFIK: TOTAL DIAMBIL PER KURIR (dalam periode) ----------
        $kurirRows = DB::table('tb_transaksi as t')
            ->join('tb_pelanggan as p', 'p.id_pelanggan', '=', 't.kurir')
            ->select('t.kurir as kurir_id', 'p.nama_pelanggan as nama_kurir', DB::raw('COUNT(*) as total'))
            ->where('t.kurir', '>', 0)
            ->whereBetween('t.created_at', [$start, $end])
            ->when($status, fn ($q) => $q->where('t.status', $status))
            ->when($kurirSel, fn ($q) => $q->whereIn('t.kurir', $kurirSel))
            ->groupBy('t.kurir', 'p.nama_pelanggan')
            ->orderByDesc('total')
            ->get();

        $kurirLabels = $kurirRows->pluck('nama_kurir')->values()->toArray();
        $kurirIds    = $kurirRows->pluck('kurir_id')->values()->toArray();
        $kurirData   = $kurirRows->pluck('total')->map(fn ($v) => (int) $v)->values()->toArray();

        // ---------- GRAFIK: LAPORAN BULANAN (tahun) / HARIAN (bulan) ----------
        $timelineLabels = [];
        $timelineKeys   = [];
        $timelineData   = [];

        if ($view === 'year') {
            $rows = DB::table('tb_transaksi')
                ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as k"), DB::raw('COUNT(*) as total'))
                ->whereBetween('created_at', [$start, $end])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($kurirSel, fn ($q) => $q->whereIn('kurir', $kurirSel))
                ->groupBy('k')
                ->pluck('total', 'k');

            for ($m = 1; $m <= 12; $m++) {
                $d = Carbon::create($year, $m, 1);
                $key = $d->format('Y-m');
                $timelineLabels[] = $d->isoFormat('MMM');
                $timelineKeys[]   = $key;
                $timelineData[]   = (int) ($rows[$key] ?? 0);
            }
            $timelineTitle = 'Laporan Bulanan';
        } else {
            $rows = DB::table('tb_transaksi')
                ->select(DB::raw('DATE(created_at) as k'), DB::raw('COUNT(*) as total'))
                ->whereBetween('created_at', [$start, $end])
                ->when($status, fn ($q) => $q->where('status', $status))
                ->when($kurirSel, fn ($q) => $q->whereIn('kurir', $kurirSel))
                ->groupBy('k')
                ->pluck('total', 'k');

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $key = $d->format('Y-m-d');
                $timelineLabels[] = (string) $d->day;
                $timelineKeys[]   = $key;
                $timelineData[]   = (int) ($rows[$key] ?? 0);
            }
            $timelineTitle = 'Laporan Harian';
        }

        // ---------- MODE PERBANDINGAN KURIR ----------
        $timelineSeries = [];
        $compareRows    = [];

        if ($compare) {
            $nameById = $kurirList->pluck('nama_pelanggan', 'id_pelanggan');
            $keyExpr  = $view === 'year' ? "DATE_FORMAT(created_at, '%Y-%m')" : 'DATE(created_at)';

            // jumlah per (periode, kurir) -> satu seri per kurir
            $raw = DB::table('tb_transaksi')
                ->select(DB::raw($keyExpr . ' as k'), 'kurir', DB::raw('COUNT(*) as total'))
                ->whereBetween('created_at', [$start, $end])
                ->whereIn('kurir', $kurirSel)
                ->when($status, fn ($q) => $q->where('status', $status))
                ->groupBy('k', 'kurir')
                ->get();

            $map = [];
            foreach ($raw as $r) {
                $map[(int) $r->kurir][$r->k] = (int) $r->total;
            }
            foreach ($kurirSel as $id) {
                $timelineSeries[] = [
                    'id'   => $id,
                    'name' => $nameById[$id] ?? ('Kurir #' . $id),
                    'data' => array_map(fn ($key) => $map[$id][$key] ?? 0, $timelineKeys),
                ];
            }

            // tabel perbandingan: total & rincian per status
            $cmp = DB::table('tb_transaksi')
                ->select('kurir', 'status', DB::raw('COUNT(*) as total'))
                ->whereBetween('created_at', [$start, $end])
                ->whereIn('kurir', $kurirSel)
                ->when($status, fn ($q) => $q->where('status', $status))
                ->groupBy('kurir', 'status')
                ->get();

            $byKurir = [];
            foreach ($cmp as $r) {
                $byKurir[(int) $r->kurir][$r->status] = (int) $r->total;
            }
            foreach ($kurirSel as $id) {
                $st = [];
                foreach (self::STATUS_LIST as $s) {
                    $st[$s] = $byKurir[$id][$s] ?? 0;
                }
                $compareRows[] = [
                    'id'       => $id,
                    'name'     => $nameById[$id] ?? ('Kurir #' . $id),
                    'total'    => array_sum($st),
                    'statuses' => $st,
                ];
            }
        }

        return view('messenger.dashboard', [
            'view'             => $view,
            'month'            => $month,
            'year'             => $year,
            'months'           => $months,
            'years'            => $years,
            'status'           => $status,
            'statusList'       => self::STATUS_LIST,
            'kurirList'        => $kurirList,
            'kurirFilter'      => $kurirSel,
            'compare'          => $compare,
            'timelineSeries'   => $timelineSeries,
            'compareRows'      => $compareRows,
            'periodLabel'      => $periodLabel,
            'periodFrom'       => $start->toDateString(),
            'periodTo'         => $end->toDateString(),
            'summary'          => $summary,
            'totalKeseluruhan' => $totalKeseluruhan,
            'kurirLabels'      => $kurirLabels,
            'kurirIds'         => $kurirIds,
            'kurirData'        => $kurirData,
            'timelineTitle'    => $timelineTitle,
            'timelineLabels'   => $timelineLabels,
            'timelineKeys'     => $timelineKeys,
            'timelineData'     => $timelineData,
        ]);
    }

    /* =====================================================
     |  DRILLDOWN: BATANG KURIR DIKLIK
     |  query opsional: from, to (Y-m-d), status
     ===================================================== */
    public function dataKurir(Request $request, $kurirId)
    {
        $kurir = DB::table('tb_pelanggan')->where('id_pelanggan', $kurirId)->first();

        if (!$kurir) {
            return response()->json(['message' => 'Kurir tidak ditemukan'], 404);
        }

        $query = $this->baseDrilldownQuery()->where('t.kurir', $kurirId);
        $this->applyRange($query, $request->input('from'), $request->input('to'));
        $this->applyStatus($query, $request->input('status'));

        $rows = $this->fetchRows($query, 300);

        return response()->json([
            'title' => 'Total Diambil oleh ' . $kurir->nama_pelanggan,
            'count' => $rows->count(),
            'rows'  => $rows,
        ]);
    }

    /* =====================================================
     |  DRILLDOWN: BATANG BULAN (Y-m) ATAU HARI (Y-m-d) DIKLIK
     |  query opsional: status, kurir
     ===================================================== */
    public function dataBulan(Request $request, $month)
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $month)) {
            $start = Carbon::parse($month)->startOfDay();
            $end   = $start->copy()->endOfDay();
            $title = 'Laporan Tanggal ' . $start->isoFormat('D MMMM Y');
        } elseif (preg_match('/^\d{4}-\d{2}$/', $month)) {
            $start = Carbon::parse($month . '-01')->startOfMonth();
            $end   = $start->copy()->endOfMonth();
            $title = 'Laporan Bulan ' . $start->isoFormat('MMMM Y');
        } else {
            return response()->json(['message' => 'Format periode tidak valid'], 422);
        }

        $query = $this->baseDrilldownQuery()->whereBetween('t.created_at', [$start, $end]);
        $this->applyStatus($query, $request->input('status'));
        $this->applyKurir($query, $request->input('kurir'));

        $rows = $this->fetchRows($query, 300);

        return response()->json([
            'title' => $title,
            'count' => $rows->count(),
            'rows'  => $rows,
        ]);
    }

    /* =====================================================
     |  HELPER
     ===================================================== */
    private function baseDrilldownQuery()
    {
        return DB::table('tb_transaksi as t')
            ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 't.pengirim')
            ->select(
                't.no_transaksi',
                't.status',
                't.nama_barang',
                't.penerima',
                't.alamat_tujuan',
                't.created_at',
                'p.nama_pelanggan as nama_pengirim'
            );
    }

    private function applyRange($query, $from, $to): void
    {
        if ($from && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) && $to && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            $query->whereBetween('t.created_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ]);
        }
    }

    private function applyStatus($query, $status): void
    {
        if ($status && in_array($status, self::STATUS_LIST, true)) {
            $query->where('t.status', $status);
        }
    }

    private function applyKurir($query, $kurir): void
    {
        $ids = collect(is_array($kurir) ? $kurir : [$kurir])
            ->filter(fn ($v) => is_scalar($v) && ctype_digit((string) $v) && (int) $v > 0)
            ->map(fn ($v) => (int) $v)
            ->values()
            ->all();

        if ($ids) {
            $query->whereIn('t.kurir', $ids);
        }
    }

    private function fetchRows($query, int $limit)
    {
        return $query->orderByDesc('t.created_at')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                $row->tanggal = Carbon::parse($row->created_at)->format('d M Y H:i');
                return $row;
            });
    }
}