<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetSub;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetFormat;
use App\Support\budget_s\BudgetPeran;
use App\Support\budget_s\BudgetRingkas;
use Illuminate\Http\Request;

/**
 * JSON untuk grafik budget vs realisasi per bulan.
 * Satu endpoint untuk semua tingkat; tingkat ditentukan oleh parameter yang dikirim:
 *   item_id                -> grafik per item
 *   sub_id                 -> grafik per subkategori
 *   kategori_id            -> grafik per kategori
 *   dept (id Dept Head, 0 = Umum) -> grafik per departemen
 *   (tanpa itu)            -> grafik semua item sesuai filter dashboard (bu)
 * dept, kategori_id, sub_id, bu, dan pt boleh digabung (mis. kategori di dalam satu dept).
 * Hak akses selalu lewat BudgetRingkas::perItem (terlihatOleh), jadi user tidak bisa melihat data milik dept lain.
 */
class GrafikController extends Controller
{
    public function data(Request $r)
    {
        $user = $r->user();
        $tahun = BudgetTahun::pilih($r->query('tahun'));
        $label = [];

        if ($r->filled('item_id')) {
            $item = BudgetItem::findOrFail($r->query('item_id'));
            abort_unless(BudgetPeran::bisaLihat($item, $user), 403);
            $ids = [$item->id];
            $label[] = 'Item: ' . $item->nama;
        } else {
            $filter = [
                'bu' => $r->query('bu'),
                'pt' => $r->query('pt'),
                'kategori_id' => $r->query('kategori_id'),
                'sub_id' => $r->query('sub_id'),
                'dept' => $r->query('dept'),
            ];

            if ($r->filled('sub_id')) {
                $sub = BudgetSub::terlihatOleh($user)->with('kategori')->find($r->query('sub_id'));
                abort_unless($sub, 403);
                $label[] = 'Sub kategori: ' . $sub->kategori->nama . ' / ' . $sub->nama;
            } elseif ($r->filled('kategori_id')) {
                $kat = BudgetKategori::terlihatOleh($user)->find($r->query('kategori_id'));
                abort_unless($kat, 403);
                $label[] = 'Kategori: ' . $kat->nama;
            }
            if ($r->query('dept') !== null && $r->query('dept') !== '') {
                $label[] = 'Dept: ' . BudgetPeran::namaDept((int) $r->query('dept'));
            }
            if ($r->filled('bu')) {
                $label[] = 'BU: ' . $r->query('bu');
            }
            if ($r->filled('pt')) {
                $label[] = 'PT: ' . $r->query('pt');
            }
            if (!$label) {
                $label[] = 'Semua item';
            }

            $ids = BudgetRingkas::perItem($user, $tahun, $filter)->pluck('id')->all();
        }

        $d = BudgetRingkas::bulanan($ids, $tahun);

        return response()->json([
            'judul' => implode(' | ', $label) . ' (' . $tahun . ')',
            'jumlah_item' => count($ids),
            'labels' => array_values(BudgetFormat::BULAN_SINGKAT),
            'budget' => array_values($d['budget']),
            'realisasi' => array_values($d['realisasi']),
        ]);
    }
}
