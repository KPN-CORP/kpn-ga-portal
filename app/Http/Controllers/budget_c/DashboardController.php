<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
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
        $filter = ['bu' => $r->query('bu'), 'pt' => $r->query('pt'), 'kategori_id' => $r->query('kategori_id')];

        // Item dimuat sekali tanpa filter; penyaringan dan pilihan dropdown dihitung dari data yang sama (hemat query)
        $semua = BudgetRingkas::perItem($user, $tahun);
        $items = BudgetRingkas::saring($semua, $filter);
        $opsi = BudgetRingkas::opsiDariItem($semua, $filter);
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
            // Item yang melebihi budget (terbesar dulu), dipakai popup di kartu Pemakaian
            'melebihi' => $items->filter(fn ($i) => $i->status['kode'] === 'lebih')
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'nama' => $i->nama,
                    'kategori' => $i->sub->kategori->nama ?? '-',
                    'sub' => $i->sub->nama ?? '-',
                    'dept' => BudgetPeran::namaDept($i->dept_head_user_id),
                    'pt' => $i->pt,
                    'budget' => (float) $i->budget,
                    'realisasi' => (float) $i->realisasi,
                    'lebih' => (float) $i->realisasi - (float) $i->budget,
                    'persen' => $i->persen,
                ])->sortByDesc('lebih')->values(),
            'filter' => $filter,
            'kategoriList' => $semua->pluck('sub.kategori')->filter()->unique('id')
                ->filter(fn ($k) => in_array((string) $k->id, $opsi['kategori'], true))->sortBy(fn ($k) => $k->kunciUrut())->values(),
            'buList' => $opsi['bu'],
            'ptList' => $opsi['pt'],
            'peran' => $peran,
        ]);
    }
}