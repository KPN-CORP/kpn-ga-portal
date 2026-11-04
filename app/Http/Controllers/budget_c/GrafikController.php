<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetFormat;
use App\Support\budget_s\BudgetPeran;
use App\Support\budget_s\BudgetRingkas;
use Illuminate\Http\Request;

/** JSON untuk grafik budget vs realisasi per bulan (satu item, atau semua item sesuai filter dashboard). */
class GrafikController extends Controller
{
    public function data(Request $r)
    {
        $user = $r->user();
        $tahun = BudgetTahun::pilih($r->query('tahun'));

        if ($r->filled('item_id')) {
            $item = BudgetItem::findOrFail($r->query('item_id'));
            abort_unless(BudgetPeran::bisaLihat($item, $user), 403);
            $ids = [$item->id];
            $judul = $item->nama;
        } else {
            $ids = BudgetRingkas::perItem($user, $tahun, [
                'bu' => $r->query('bu'),
                'kategori_id' => $r->query('kategori_id'),
            ])->pluck('id')->all();
            $judul = 'Semua item sesuai filter';
        }

        $d = BudgetRingkas::bulanan($ids, $tahun);

        return response()->json([
            'judul' => $judul . ' (' . $tahun . ')',
            'labels' => array_values(BudgetFormat::BULAN_SINGKAT),
            'budget' => array_values($d['budget']),
            'realisasi' => array_values($d['realisasi']),
        ]);
    }
}
