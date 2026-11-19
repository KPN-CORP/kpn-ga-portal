<?php

namespace App\Http\Controllers\IDCard;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GrafikController extends IDCardBaseController
{
    public function index(Request $request)
    {
        if (!$this->isSuperAdmin() && empty($this->getAdminBUAccess())) {
            return redirect()->route('no-access')->with('error', 'Anda tidak memiliki akses ke halaman grafik.');
        }

        $adminBU  = $this->getAdminBUAccess();
        $isSuper  = $this->isSuperAdmin();

        // Query dasar yang sudah dibatasi hak akses BU
        $base = function () use ($adminBU, $isSuper) {
            $q = DB::table('request_idcard');
            if (!$isSuper && !empty($adminBU)) {
                $q->whereIn('bisnis_unit_id', $adminBU);
            }
            return $q;
        };

        // ---- Filter tahun ("all" = semua tahun) ----
        $tahunList = $base()->selectRaw('YEAR(created_at) as y')
            ->whereNotNull('created_at')
            ->distinct()->orderByDesc('y')->pluck('y')->map(fn ($y) => (int) $y)->all();

        $tahun = $request->get('tahun', 'all');
        if ($tahun !== 'all' && !in_array((int) $tahun, $tahunList, true)) {
            $tahun = 'all';
        }
        $withTahun = function ($q) use ($tahun) {
            if ($tahun !== 'all') {
                $q->whereYear('created_at', (int) $tahun);
            }
            return $q;
        };

        // ---- Ringkasan ----
        $statusCount = $withTahun($base())
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')->pluck('total', 'status');

        $summary = [
            'total'    => (int) $statusCount->sum(),
            'pending'  => (int) ($statusCount['pending'] ?? 0),
            'approved' => (int) ($statusCount['approved'] ?? 0),
            'rejected' => (int) ($statusCount['rejected'] ?? 0),
        ];

        // ---- Per Bisnis Unit (dikelompokkan per status) ----
        $bisnisUnits = DB::table('tb_bisnis_unit');
        if (!$isSuper && !empty($adminBU)) {
            $bisnisUnits->whereIn('id_bisnis_unit', $adminBU);
        }
        $bisnisUnits = $bisnisUnits->orderBy('nama_bisnis_unit')->get();

        $perBu = $withTahun($base())
            ->select('bisnis_unit_id', 'status', DB::raw('count(*) as total'))
            ->groupBy('bisnis_unit_id', 'status')->get();

        $chartData = [];
        foreach ($bisnisUnits as $bu) {
            $chartData[$bu->id_bisnis_unit] = ['nama' => $bu->nama_bisnis_unit, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
        }
        foreach ($perBu as $row) {
            if (isset($chartData[$row->bisnis_unit_id])) {
                $chartData[$row->bisnis_unit_id][$row->status] = (int) $row->total;
            }
        }
        $chartData    = array_values($chartData);
        $chartLabels  = array_column($chartData, 'nama');
        $pendingData  = array_column($chartData, 'pending');
        $approvedData = array_column($chartData, 'approved');
        $rejectedData = array_column($chartData, 'rejected');

        // ---- Per Kategori ----
        $kategoriCount = $withTahun($base())
            ->select('kategori', DB::raw('count(*) as total'))
            ->groupBy('kategori')->pluck('total', 'kategori');

        $kategoriLabels = [];
        $kategoriData   = [];
        foreach ($this->kategoriLabels as $key => $label) {
            $kategoriLabels[] = $label;
            $kategoriData[]   = (int) ($kategoriCount[$key] ?? 0);
        }

        // ---- Tren bulanan ----
        // Tahun dipilih  : Jan - Des tahun tersebut
        // "Semua tahun"  : 12 bulan terakhir
        if ($tahun !== 'all') {
            $months = [];
            for ($m = 1; $m <= 12; $m++) {
                $months[] = sprintf('%04d-%02d', (int) $tahun, $m);
            }
        } else {
            $months = [];
            $cursor = now()->startOfMonth()->subMonths(11);
            for ($i = 0; $i < 12; $i++) {
                $months[] = $cursor->format('Y-m');
                $cursor->addMonth();
            }
        }

        $trend = $base()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, count(*) as total")
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') BETWEEN ? AND ?", [reset($months), end($months)])
            ->groupBy('ym')->pluck('total', 'ym');

        $trendLabels = [];
        $trendData   = [];
        foreach ($months as $ym) {
            $trendLabels[] = \Carbon\Carbon::createFromFormat('Y-m-d', $ym . '-01')->format('m/Y');
            $trendData[]   = (int) ($trend[$ym] ?? 0);
        }

        return view('idcard.grafik', compact(
            'tahun', 'tahunList', 'summary',
            'chartLabels', 'pendingData', 'approvedData', 'rejectedData',
            'kategoriLabels', 'kategoriData',
            'trendLabels', 'trendData'
        ));
    }
}
