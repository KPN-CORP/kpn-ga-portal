<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Http\Requests\budget_r\SalinTahunRequest;
use App\Http\Requests\budget_r\SimpanBudgetBulanRequest;
use App\Http\Requests\budget_r\SimpanItemRequest;
use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\BudgetBulan;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetItemTahun;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetPicHead;
use App\Models\budget_m\BudgetTagihan;
use App\Models\budget_m\BudgetSub;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetCatat;
use App\Support\budget_s\BudgetKunci;
use App\Support\budget_s\BudgetPeran;
use App\Support\budget_s\BudgetRingkas;
use App\Support\budget_s\BudgetSalin;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

        // Halaman ini berat (12 kotak angka per item), jadi item baru ditampilkan setelah ada filter atau pencarian.
        $q = trim((string) $r->query('q'));
        $adaFilter = $r->filled('kategori_id') || $r->filled('sub_id') || $r->filled('bu') || $r->filled('pt') || $q !== '';
        $perHalaman = 1; // diisi ulang di bawah: semua item tampil, tidak dibagi halaman

        $semua = $adaFilter
            ? BudgetItem::terlihatOleh($user)->berlakuDi($tahun)
                ->with(['sub.kategori', 'deptHead', 'pic', 'tahunTerdaftar'])
                ->when($r->query('kategori_id'), fn ($x, $v) => $x->whereHas('sub', fn ($s) => $s->where('kategori_id', $v)))
                ->when($r->query('sub_id'), fn ($x, $v) => $x->where('sub_id', $v))
                ->when($r->query('bu'), fn ($x, $v) => $x->where('bu', $v))
                ->when($r->query('pt'), fn ($x, $v) => $x->where('pt', $v))
                ->when($q !== '', fn ($x) => $x->where('nama', 'like', '%' . $q . '%'))
                ->get()
                ->sortBy(fn ($i) => [
                    $i->dept_head_user_id ? 0 : 1, mb_strtolower(BudgetPeran::namaDept($i->dept_head_user_id)), mb_strtolower(BudgetPeran::namaOrang($i->dept_head_user_id)),
                    (int) $i->sub->kategori->urut, mb_strtolower($i->sub->kategori->nama), (int) $i->sub->urut, mb_strtolower($i->sub->nama), mb_strtolower($i->nama),
                ])
                ->values()
            : collect();

        $opsi = BudgetRingkas::opsiFilter($user, $tahun, ['kategori_id' => $r->query('kategori_id'), 'sub_id' => $r->query('sub_id'), 'bu' => $r->query('bu'), 'pt' => $r->query('pt')]);
        $subFilterList = BudgetSub::with('kategori')->whereIn('id', $opsi['sub'])->get()
            ->sortBy(fn ($x) => [...$x->kategori->kunciUrut(), (int) $x->urut, mb_strtolower($x->nama)])->values();

        $perHalaman = max(1, $semua->count());
        $hal = 1;
        $items = $semua->forPage($hal, $perHalaman)->values();
        $paginator = new LengthAwarePaginator($items, $semua->count(), $perHalaman, $hal, ['path' => $r->url(), 'query' => $r->query()]);

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

        // Pilihan sumber untuk "Salin dari tahun lain": item yang tampil di tiap tahun dan boleh Anda kelola
        $dataSalin = ['kat' => [], 'sub' => [], 'item' => [], 'perTahun' => (object) []];
        if ($peran === BudgetPeran::ADMIN || $peran === BudgetPeran::DEPT_HEAD) {
            $perTahun = [];
            $gab = collect();
            foreach (BudgetTahun::daftar() as $t) {
                $it = BudgetItem::terlihatOleh($user)->berlakuDi((int) $t)->with('sub.kategori')->get();
                $perTahun[(int) $t] = $it->pluck('id')->all();
                $gab = $gab->concat($it);
            }
            $gab = $gab->unique('id')->values();
            $dataSalin = [
                'kat' => $gab->pluck('sub.kategori')->filter()->unique('id')->map(fn ($k) => ['id' => $k->id, 'nama' => $k->nama])->values(),
                'sub' => $gab->pluck('sub')->filter()->unique('id')->map(fn ($x) => ['id' => $x->id, 'kategori_id' => $x->kategori_id, 'nama' => $x->nama])->values(),
                'item' => $gab->map(fn ($i) => ['id' => $i->id, 'sub_id' => $i->sub_id, 'nama' => $i->nama])->values(),
                'perTahun' => (object) $perTahun,
            ];
        }

        return view('budget_p.budget_all.index', [
            'tahun' => $tahun,
            'daftarTahun' => BudgetTahun::daftar(),
            'tahunRow' => $tahunRow,
            'terkunci' => (bool) ($tahunRow->terkunci ?? false),
            'peran' => $peran,
            'items' => $items,
            'paginator' => $paginator,
            'adaFilter' => $adaFilter,
            'dataSalin' => $dataSalin,
            'nominal' => $nominal,
            'itemTerkunci' => BudgetKunci::itemIdTerkunci($items->pluck('id')->all()),
            'bolehIsi' => $bolehIsi,
            'bolehUbah' => $bolehUbah,
            'kategoriList' => BudgetKategori::terlihatOleh($user)->where('aktif', true)->get()->sortBy(fn ($k) => $k->kunciUrut())
                ->filter(fn ($k) => in_array((string) $k->id, $opsi['kategori'], true))->values(),
            'subList' => BudgetSub::terlihatOleh($user)->with('kategori')->where('aktif', true)->get()
                ->sortBy(fn ($s) => [...$s->kategori->kunciUrut(), (int) $s->urut, mb_strtolower($s->nama)])->values(),
            'buList' => $opsi['bu'],
            'ptList' => $opsi['pt'],
            'kepala' => $kepala,
            'headTetap' => $headTetap,
            'picMap' => $picMap,
            'subFilterList' => $subFilterList,
            'filter' => ['bu' => $r->query('bu'), 'pt' => $r->query('pt'), 'kategori_id' => $r->query('kategori_id'), 'sub_id' => $r->query('sub_id'), 'q' => $r->query('q')],
        ]);
    }

    public function simpanItem(SimpanItemRequest $r)
    {
        $data = Arr::except($r->validated(), ['id', 'mode', 'tahun_berlaku', 'tahun_mulai']);
        $data['aktif'] = $r->boolean('aktif', true);
        $data['pic_user_id'] = $r->filled('pic_user_id') ? (int) $r->input('pic_user_id') : null;
        $data['dept_head_user_id'] = BudgetPeran::tentukanKepala($r->user(), $r->input('dept_head_user_id'));

        if ($r->filled('id')) {
            $item = BudgetItem::findOrFail($r->input('id'));

            // Ganti nama / pindah subkategori mulai tahun tertentu: item lama tetap utuh untuk tahun-tahun sebelumnya.
            $mulai = (int) $r->input('tahun_berlaku');
            if ($r->input('mode') === 'mulai' && $mulai > 0
                && ($item->tahun_mulai === null || $mulai > (int) $item->tahun_mulai)) {
                return $this->itemMulaiTahun($r, $item, $data, $mulai);
            }

            if ($item->aktif && !$data['aktif'] && ($kunci = BudgetKunci::tahunTerkunciItem($item->id))) {
                return back()->withErrors(['budget' => 'Item "' . $item->nama . '" tidak bisa dinonaktifkan karena punya data di tahun yang dikunci (' . implode(', ', $kunci) . '). Minta Admin membuka kunci dulu.']);
            }
            $sebelum = $item->getAttributes();
            $item->update($data);
            BudgetCatat::tulis('ubah', 'item', $item->id, $item->nama, $sebelum, $item->getAttributes(), ['item_id' => $item->id]);
            return back()->with('ok', 'Item diperbarui di semua tahun.');
        }

        if ($r->filled('tahun_mulai')) {
            $data['tahun_mulai'] = (int) $r->input('tahun_mulai');
        }
        $baru = BudgetItem::create($data);
        $baru->daftarkanDi((int) ($r->input('tahun_mulai') ?: BudgetTahun::pilih(null)));
        BudgetCatat::tulis('tambah', 'item', $baru->id, $baru->nama, null, $baru->getAttributes(), ['item_id' => $baru->id]);
        return back()->with('ok', 'Item baru ditambahkan.');
    }

    /**
     * Item lama berakhir di (mulai - 1). Item baru (nama/sub/detail baru) berlaku sejak "mulai".
     * Angka budget dan tagihan tahun >= mulai ikut dipindah ke item baru; tahun sebelumnya tidak disentuh,
     * jadi laporan tahun lama tetap memakai nama lama dan tidak ada data yang hilang.
     */
    protected function itemMulaiTahun(SimpanItemRequest $r, BudgetItem $lama, array $data, int $mulai)
    {
        $tahunData = BudgetBulan::where('item_id', $lama->id)->where('tahun', '>=', $mulai)->pluck('tahun')
            ->merge(BudgetTagihan::where('item_id', $lama->id)->where('tahun', '>=', $mulai)->pluck('tahun'))
            ->map(fn ($v) => (int) $v)->unique()->values();

        if (!BudgetPeran::isAdmin($r->user())) {
            $terkunci = $tahunData->filter(fn ($t) => BudgetTahun::terkunci($t))->values();
            if ($terkunci->isNotEmpty()) {
                return back()->withErrors(['budget' => 'Item "' . $lama->nama . '" punya data di tahun yang dikunci (' . $terkunci->implode(', ') . '), jadi tidak bisa dipindah. Minta Admin membuka kunci dulu.']);
            }
        }

        $baru = DB::transaction(function () use ($lama, $data, $mulai) {
            $baru = BudgetItem::create($data + [
                'tahun_mulai' => $mulai,
                'tahun_akhir' => $lama->tahun_akhir,
                'item_asal_id' => $lama->id,
            ]);
            BudgetBulan::where('item_id', $lama->id)->where('tahun', '>=', $mulai)->update(['item_id' => $baru->id]);
            BudgetTagihan::where('item_id', $lama->id)->where('tahun', '>=', $mulai)->update(['item_id' => $baru->id]);
            // Item baru terdaftar di tahun tujuan dan tahun-tahun berikutnya yang sebelumnya terdaftar untuk item lama
            $tahunDaftar = BudgetItemTahun::where('item_id', $lama->id)->where('tahun', '>=', $mulai)->pluck('tahun')
                ->map(fn ($v) => (int) $v)->push($mulai)->unique();
            foreach ($tahunDaftar as $y) {
                $baru->daftarkanDi($y);
            }
            $lama->update(['tahun_akhir' => $mulai - 1]);
            return $baru;
        });

        BudgetCatat::tulis('pindah', 'item', $baru->id,
            'Item "' . $lama->nama . '" diganti "' . $baru->nama . '" mulai ' . $mulai,
            ['item_lama' => $lama->id, 'tahun_akhir_lama' => $mulai - 1],
            ['item_baru' => $baru->id, 'tahun_mulai' => $mulai, 'tahun_dipindah' => $tahunData->sort()->values()->all()],
            ['item_id' => $baru->id, 'tahun' => $mulai]);

        return redirect()->route('budgeting.budget.index', ['tahun' => $mulai])
            ->with('ok', 'Item "' . $baru->nama . '" berlaku mulai ' . $mulai . '. Tahun sebelum itu tetap memakai "' . $lama->nama . '".');
    }

    /** Item tidak pernah dihapus permanen: hanya dinonaktifkan, dan ditolak bila punya data di tahun yang dikunci. */
    public function hapusItem(Request $r, BudgetItem $item)
    {
        abort_unless(BudgetPeran::bisaUbahItem($item, $r->user()), 403);

        if ($kunci = BudgetKunci::tahunTerkunciItem($item->id)) {
            return back()->withErrors(['budget' => 'Item "' . $item->nama . '" tidak bisa dinonaktifkan karena punya data di tahun yang dikunci (' . implode(', ', $kunci) . '). Minta Admin membuka kunci dulu.']);
        }

        $sebelum = $item->getAttributes();
        $item->update(['aktif' => false]);
        BudgetCatat::tulis('nonaktifkan', 'item', $item->id, $item->nama, $sebelum, $item->getAttributes(), ['item_id' => $item->id]);

        return back()->with('ok', 'Item "' . $item->nama . '" dinonaktifkan. Item tidak dihapus supaya riwayat budget dan tagihan tetap aman.');
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

        $sebelum = BudgetBulan::where('item_id', $item->id)->where('tahun', $tahun)
            ->pluck('nominal', 'bulan')->map(fn ($v) => (float) $v)->all();
        $sesudah = [];

        DB::transaction(function () use ($r, $item, $tahun, &$sesudah) {
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
                $sesudah[$b] = (float) $v;
            }
        });

        // Hanya dicatat bila ada angka yang benar-benar berubah. Format: {bulan: nominal}
        if ($sebelum != $sesudah) {
            BudgetCatat::tulis('ubah', 'budget_bulan', $item->id, $item->nama . ' (' . $tahun . ')', $sebelum, $sesudah, ['item_id' => $item->id, 'tahun' => $tahun]);
        }

        return back()->with('ok', 'Budget "' . $item->nama . '" tahun ' . $tahun . ' disimpan.');
    }

    public function salinTahun(SalinTahunRequest $r)
    {
        $ke = (int) $r->input('ke');
        if (BudgetTahun::terkunci($ke) && !BudgetPeran::isAdmin($r->user())) {
            return back()->withErrors(['budget' => 'Tahun ' . $ke . ' sudah dikunci.']);
        }
        $hasil = BudgetSalin::jalankan(
            (int) $r->input('dari'), $ke, $r->user(), $r->boolean('timpa'),
            ['kategori_id' => $r->input('kategori_id'), 'sub_id' => $r->input('sub_id'), 'item_id' => $r->input('item_id')],
            $r->boolean('angka', true)
        );
        if ($hasil['item'] === 0 && $hasil['baris'] === 0) {
            return back()->withErrors(['budget' => 'Tidak ada yang disalin. Item yang cocok sudah ada di tahun ' . $ke . ', atau tidak ada item yang cocok dengan pilihan Anda di tahun ' . $r->input('dari') . '.']);
        }
        return redirect()->route('budgeting.budget.index', ['tahun' => $ke])
            ->with('ok', $hasil['item'] . ' item baru dan ' . $hasil['baris'] . ' baris budget bulanan disalin dari ' . $r->input('dari') . ' ke ' . $ke . '. Pilih filter untuk melihatnya.');
    }
}