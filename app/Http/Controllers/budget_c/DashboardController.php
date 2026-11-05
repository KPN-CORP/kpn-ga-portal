<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetPeran;
use App\Support\budget_s\BudgetStatus;
use App\Support\budget_s\BudgetRingkas;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();
        $tahun = BudgetTahun::pilih($r->query('tahun'));
        $filter = ['bu' => $r->query('bu'), 'kategori_id' => $r->query('kategori_id')];

        $items = BudgetRingkas::perItem($user, $tahun, $filter);
        $peran = BudgetPeran::peran($user);

        // Admin: data dipisah per Dept Head supaya jelas milik siapa
        $perDept = $peran === BudgetPeran::ADMIN
            ? $items->groupBy('dept_head_user_id')->map(function ($g, $id) {
                $b = (float) $g->sum('budget');
                $r = (float) $g->sum('realisasi');
                return [
                    'id' => (int) $id,
                    'nama' => BudgetPeran::namaDept($id),
                    'orang' => BudgetPeran::namaOrang($id),
                    'jumlah' => $g->count(),
                    'bu' => $g->pluck('bu')->filter()->unique()->sort()->values()->implode(', '),
                    'budget' => $b,
                    'realisasi' => $r,
                    'persen' => $b > 0 ? round($r / $b * 100, 1) : null,
                    'status' => BudgetStatus::dari($b, $r),
                    'perKategori' => BudgetRingkas::perKategori($g),
                ];
            })->sortBy('nama')->values()
            : null;

        return view('budget_p.dashboard.index', [
            'tahun' => $tahun,
            'daftarTahun' => BudgetTahun::daftar(),
            'tahunRow' => BudgetTahun::where('tahun', $tahun)->first(),
            'kpi' => BudgetRingkas::kpi($items),
            'perKategori' => BudgetRingkas::perKategori($items),
            'perDept' => $perDept,
            'filter' => $filter,
            'kategoriList' => BudgetKategori::terlihatOleh($user)->where('aktif', true)->orderBy('urut')->get(),
            'buList' => BudgetRingkas::buTersedia($user),
            'peran' => $peran,
        ]);
    }
}