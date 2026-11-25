<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Models\budget_m\BudgetBulan;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetPicHead;
use App\Models\budget_m\BudgetSub;
use App\Models\budget_m\BudgetTagihan;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetCatat;
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

        $kategori = BudgetKategori::terlihatOleh($user)->where('aktif', true)
            ->with(['subs' => fn ($q) => $q->where('aktif', true)])->get()->sortBy(fn ($k) => $k->kunciUrut())->values();

        $jumlah = BudgetItem::terlihatOleh($user)->berlakuDi($tahun)
            ->selectRaw('sub_id, COUNT(*) AS c')->groupBy('sub_id')->pluck('c', 'sub_id');

        // Kategori dan subkategori hanya tampil bila punya item di tahun ini (tahun baru kosong sampai item disalin)
        $kategori = $kategori->each(fn ($k) => $k->setRelation('subs', $k->subs->filter(fn ($s) => ($jumlah[$s->id] ?? 0) > 0)->values()))
            ->filter(fn ($k) => $k->subs->isNotEmpty())->values();

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

        // Rincian per bulan: semua tagihan item ini di tahun itu (satu bulan bisa punya banyak invoice)
        $rincian = BudgetTagihan::with(['penginput', 'isian.form'])->where('item_id', $item->id)->where('tahun', $tahun)
            ->orderBy('bulan')->orderBy('id')->get()->groupBy('bulan');

        // Admin, Dept Head pemilik item, dan PIC dari Dept Head tersebut boleh menunjuk PIC (bawahan Dept Head pemilik item) langsung di popup ini.
        $bisaAturPic = $this->bisaAturPic($item, $r->user());
        $daftarPic = $bisaAturPic
            ? BudgetPicHead::with('pic')->where('dept_head_user_id', $item->dept_head_user_id)->where('aktif', true)->get()
                ->sortBy(fn ($p) => $p->pic->name ?? '')->values()
            : collect();

        return view('budget_p.partials.panel-item', [
            'item' => $item,
            'tahun' => $tahun,
            'budget' => $budget,
            'real' => $real,
            'rincian' => $rincian,
            'bisaUbah' => BudgetPeran::bisaUbahItem($item, $r->user()),
            'bisaAturPic' => $bisaAturPic,
            'daftarPic' => $daftarPic,
        ]);
    }

    /** Admin (semua item), Dept Head dan PIC (item departemennya): tunjuk atau kosongkan PIC. PIC harus bawahan aktif Dept Head pemilik item. */
    public function simpanPic(Request $r, BudgetItem $item)
    {
        $user = $r->user();
        abort_unless($this->bisaAturPic($item, $user), 403);

        $r->validate(['pic_user_id' => ['nullable', 'integer']], [], ['pic_user_id' => 'PIC']);
        $picId = $r->filled('pic_user_id') ? (int) $r->input('pic_user_id') : null;

        if ($picId !== null) {
            // PIC harus bawahan aktif Dept Head pemilik item (untuk Admin pun begitu)
            $bawahan = BudgetPicHead::where('dept_head_user_id', $item->dept_head_user_id)
                ->where('pic_user_id', $picId)->where('aktif', true)->exists();
            if (!$bawahan) {
                return back()->withErrors(['pic' => 'PIC yang dipilih bukan bawahan aktif Dept Head pemilik item ini. PIC ditambahkan oleh Dept Head di menu PIC Saya.']);
            }
        }

        $sebelum = $item->getAttributes();
        $item->update(['pic_user_id' => $picId]);
        BudgetCatat::tulis('ubah', 'item', $item->id, $item->nama, $sebelum, $item->getAttributes(), ['item_id' => $item->id]);

        return back()->with('ok', $picId ? 'PIC item "' . $item->nama . '" disimpan.' : 'PIC item "' . $item->nama . '" dikosongkan.');
    }

    private function bisaAturPic(BudgetItem $item, $user): bool
    {
        return BudgetPeran::bisaAturPic($item, $user);
    }
}