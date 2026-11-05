<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Http\Requests\budget_r\SalinTahunRequest;
use App\Http\Requests\budget_r\SimpanBudgetBulanRequest;
use App\Http\Requests\budget_r\SimpanItemRequest;
use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\BudgetBulan;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetPicHead;
use App\Models\budget_m\BudgetSub;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetPeran;
use App\Support\budget_s\BudgetRingkas;
use App\Support\budget_s\BudgetSalin;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BudgetAllController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();
        $peran = BudgetPeran::peran($user);
        $tahun = BudgetTahun::pilih($r->query('tahun'));
        $tahunRow = BudgetTahun::where('tahun', $tahun)->first();

        $items = BudgetItem::terlihatOleh($user)->where('aktif', true)
            ->with(['sub.kategori', 'deptHead', 'pic'])
            ->when($r->query('kategori_id'), fn ($q, $v) => $q->whereHas('sub', fn ($s) => $s->where('kategori_id', $v)))
            ->when($r->query('bu'), fn ($q, $v) => $q->where('bu', $v))
            ->when($r->query('q'), fn ($q, $v) => $q->where('nama', 'like', '%' . $v . '%'))
            ->get()
            ->sortBy(fn ($i) => [$i->sub->kategori->urut, $i->sub->urut, $i->nama])
            ->values();

        $nominal = BudgetBulan::where('tahun', $tahun)->whereIn('item_id', $items->pluck('id'))->get()
            ->groupBy('item_id')->map(fn ($g) => $g->pluck('nominal', 'bulan'));

        $bolehIsi = [];
        $bolehUbah = [];
        foreach ($items as $i) {
            $bolehIsi[$i->id] = BudgetPeran::bisaIsiBudget($i, $tahun, $user);
            $bolehUbah[$i->id] = BudgetPeran::bisaUbahItem($i, $user);
        }

        // Daftar untuk form item: Admin memilih Dept Head; PIC difilter per Dept Head lewat JS.
        $kepala = collect();
        if ($peran === BudgetPeran::ADMIN) {
            $kepala = BudgetAkses::where('peran', BudgetPeran::DEPT_HEAD)->where('aktif', true)->get()
                ->map(fn ($a) => ['id' => $a->user_id, 'nama' => BudgetPeran::namaDeptLengkap($a->user_id)])->values();
        } elseif ($peran === BudgetPeran::PIC) {
            $kepala = collect(BudgetPeran::kepalaIds($user))
                ->map(fn ($id) => ['id' => $id, 'nama' => BudgetPeran::namaDept($id)])->values();
        }
        $headTetap = $peran === BudgetPeran::DEPT_HEAD ? (int) $user->id
            : ($peran === BudgetPeran::PIC && $kepala->count() === 1 ? (int) $kepala->first()['id'] : null);
        $picMap = [];
        BudgetPicHead::with('pic')->where('aktif', true)
            ->when($peran === BudgetPeran::DEPT_HEAD || $peran === BudgetPeran::PIC, fn ($q) => $q->whereIn('dept_head_user_id', BudgetPeran::kepalaIds($user)))
            ->get()->each(function ($p) use (&$picMap) {
                $picMap[$p->dept_head_user_id][] = ['id' => $p->pic_user_id, 'nama' => $p->pic?->name ?? ('#' . $p->pic_user_id)];
            });

        return view('budget_p.budget_all.index', [
            'tahun' => $tahun,
            'daftarTahun' => BudgetTahun::daftar(),
            'tahunRow' => $tahunRow,
            'terkunci' => (bool) ($tahunRow->terkunci ?? false),
            'peran' => $peran,
            'items' => $items,
            'nominal' => $nominal,
            'bolehIsi' => $bolehIsi,
            'bolehUbah' => $bolehUbah,
            'kategoriList' => BudgetKategori::terlihatOleh($user)->where('aktif', true)->orderBy('urut')->get(),
            'subList' => BudgetSub::terlihatOleh($user)->with('kategori')->where('aktif', true)->get()
                ->sortBy(fn ($s) => [$s->kategori->urut, $s->urut])->values(),
            'buList' => BudgetRingkas::buTersedia($user),
            'kepala' => $kepala,
            'headTetap' => $headTetap,
            'picMap' => $picMap,
            'filter' => ['bu' => $r->query('bu'), 'kategori_id' => $r->query('kategori_id'), 'q' => $r->query('q')],
        ]);
    }

    public function simpanItem(SimpanItemRequest $r)
    {
        $data = Arr::except($r->validated(), ['id']);
        $data['aktif'] = $r->boolean('aktif', true);
        $data['pic_user_id'] = $r->filled('pic_user_id') ? (int) $r->input('pic_user_id') : null;
        $data['dept_head_user_id'] = BudgetPeran::tentukanKepala($r->user(), $r->input('dept_head_user_id'));

        if ($r->filled('id')) {
            BudgetItem::findOrFail($r->input('id'))->update($data);
            return back()->with('ok', 'Item diperbarui.');
        }
        BudgetItem::create($data);
        return back()->with('ok', 'Item baru ditambahkan.');
    }

    public function hapusItem(Request $r, BudgetItem $item)
    {
        abort_unless(BudgetPeran::bisaUbahItem($item, $r->user()), 403);

        // Item yang sudah punya tagihan tidak dihapus supaya riwayat realisasi aman: dinonaktifkan saja.
        if ($item->tagihan()->exists()) {
            $item->update(['aktif' => false]);
            return back()->with('ok', 'Item sudah punya tagihan, jadi dinonaktifkan (tidak dihapus).');
        }
        DB::transaction(function () use ($item) {
            $item->bulan()->delete();
            $item->delete();
        });
        return back()->with('ok', 'Item dihapus.');
    }

    public function simpanBulan(SimpanBudgetBulanRequest $r)
    {
        $item = BudgetItem::findOrFail($r->input('item_id'));
        $tahun = (int) $r->input('tahun');
        $user = $r->user();

        if (!BudgetPeran::bisaIsiBudget($item, $tahun, $user)) {
            $pesan = BudgetPeran::bisaLihat($item, $user) && BudgetTahun::terkunci($tahun)
                ? 'Tahun ' . $tahun . ' sudah dikunci. Hubungi Admin untuk membuka kunci.'
                : 'Anda tidak berhak mengisi budget item ini.';
            return back()->withErrors(['budget' => $pesan]);
        }

        DB::transaction(function () use ($r, $item, $tahun) {
            foreach (range(1, 12) as $b) {
                $v = $r->input("nominal.$b");
                if ($v === null || $v === '') {
                    BudgetBulan::where('item_id', $item->id)->where('tahun', $tahun)->where('bulan', $b)->delete();
                    continue;
                }
                BudgetBulan::updateOrCreate(
                    ['item_id' => $item->id, 'tahun' => $tahun, 'bulan' => $b],
                    ['nominal' => $v]
                );
            }
        });

        return back()->with('ok', 'Budget "' . $item->nama . '" tahun ' . $tahun . ' disimpan.');
    }

    public function salinTahun(SalinTahunRequest $r)
    {
        $ke = (int) $r->input('ke');
        if (BudgetTahun::terkunci($ke) && !BudgetPeran::isAdmin($r->user())) {
            return back()->withErrors(['budget' => 'Tahun ' . $ke . ' sudah dikunci.']);
        }
        $n = BudgetSalin::jalankan((int) $r->input('dari'), $ke, $r->user(), $r->boolean('timpa'));
        return redirect()->route('budgeting.budget.index', ['tahun' => $ke])
            ->with('ok', $n . ' baris budget bulanan disalin dari ' . $r->input('dari') . ' ke ' . $ke . '.');
    }
}