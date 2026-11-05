<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Models\budget_m\BudgetBulan;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetSub;
use App\Models\budget_m\BudgetTagihan;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetPeran;
use App\Support\budget_s\BudgetRingkas;
use Illuminate\Http\Request;

class KategoriController extends Controller
{
    /** Kategori di kiri (klik: sub terbuka), item sub terpilih di kanan, klik item: popup detail. */
    public function index(Request $r)
    {
        $user = $r->user();
        $tahun = BudgetTahun::pilih($r->query('tahun'));

        $kategori = BudgetKategori::terlihatOleh($user)->where('aktif', true)->orderBy('urut')
            ->with(['subs' => fn ($q) => $q->where('aktif', true)])->get();

        $jumlah = BudgetItem::terlihatOleh($user)->where('aktif', true)
            ->selectRaw('sub_id, COUNT(*) AS c')->groupBy('sub_id')->pluck('c', 'sub_id');

        $subAktif = $r->filled('sub') ? BudgetSub::terlihatOleh($user)->with('kategori')->find($r->query('sub')) : null;
        $items = $subAktif ? BudgetRingkas::perItem($user, $tahun, ['sub_id' => $subAktif->id]) : collect();

        return view('budget_p.kategori.index', [
            'tahun' => $tahun,
            'daftarTahun' => BudgetTahun::daftar(),
            'tahunRow' => BudgetTahun::where('tahun', $tahun)->first(),
            'kategori' => $kategori,
            'jumlah' => $jumlah,
            'subAktif' => $subAktif,
            'items' => $items,
        ]);
    }

    /** Potongan HTML untuk popup detail item. */
    public function item(Request $r, BudgetItem $item)
    {
        abort_unless(BudgetPeran::bisaLihat($item, $r->user()), 403);
        $tahun = BudgetTahun::pilih($r->query('tahun'));
        $item->load('sub.kategori', 'deptHead', 'pic');

        $budget = BudgetBulan::where('item_id', $item->id)->where('tahun', $tahun)->pluck('nominal', 'bulan');
        $real = BudgetTagihan::where('item_id', $item->id)->where('tahun', $tahun)
            ->selectRaw('bulan, SUM(total) AS t')->groupBy('bulan')->pluck('t', 'bulan');

        return view('budget_p.partials.panel-item', [
            'item' => $item,
            'tahun' => $tahun,
            'budget' => $budget,
            'real' => $real,
            'bisaUbah' => BudgetPeran::bisaUbahItem($item, $r->user()),
        ]);
    }
}