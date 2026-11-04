<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetPeran;
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

        return view('budget_p.dashboard.index', [
            'tahun' => $tahun,
            'daftarTahun' => BudgetTahun::daftar(),
            'tahunRow' => BudgetTahun::where('tahun', $tahun)->first(),
            'kpi' => BudgetRingkas::kpi($items),
            'perKategori' => BudgetRingkas::perKategori($items),
            'filter' => $filter,
            'kategoriList' => BudgetKategori::where('aktif', true)->orderBy('urut')->get(),
            'buList' => BudgetRingkas::buTersedia($user),
            'peran' => BudgetPeran::peran($user),
        ]);
    }
}
