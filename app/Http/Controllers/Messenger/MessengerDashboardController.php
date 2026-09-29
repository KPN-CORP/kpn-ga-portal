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
 *  - index()      : dashboard (filter Tampilan/Bulan/Tahun/Status,
 *                   grafik laporan bulanan/harian + total per kurir)
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

        return view('messenger.dashboard', [
            'view'             => $view,
            'month'            => $month,
            'year'             => $year,
            'months'           => $months,
            'years'            => $years,
            'status'           => $status,
            'statusList'       => self::STATUS_LIST,
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
     |  query opsional: status
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