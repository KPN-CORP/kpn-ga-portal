<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Http\Requests\budget_r\SalinTahunRequest;
use App\Http\Requests\budget_r\SimpanBudgetBulanRequest;
use App\Http\Requests\budget_r\SimpanItemRequest;
use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\BudgetBulan;
use App\Models\budget_m\BudgetForm;
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

        $q = trim((string) $r->query('q'));
        $adaFilter = $r->filled('kategori_id') || $r->filled('sub_id') || $r->filled('bu') || $r->filled('pt') || $q !== '';
        $tampilNonaktif = $r->boolean('nonaktif');
        $perHalaman = 25;

        // Semua item tahun ini selalu tampil (dibagi per halaman); filter hanya mempersempit.
        $dasar = BudgetItem::terlihatOleh($user);
        $dasar = $tampilNonaktif ? $dasar->termasukNonaktifDi($tahun) : $dasar->berlakuDi($tahun);
        $semua = $dasar
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
            ->values();

        $opsi = BudgetRingkas::opsiFilter($user, $tahun, ['kategori_id' => $r->query('kategori_id'), 'sub_id' => $r->query('sub_id'), 'bu' => $r->query('bu'), 'pt' => $r->query('pt')]);
        $subFilterList = BudgetSub::with('kategori')->whereIn('id', $opsi['sub'])->get()
            ->sortBy(fn ($x) => [...$x->kategori->kunciUrut(), (int) $x->urut, mb_strtolower($x->nama)])->values();

        $halTerakhir = max(1, (int) ceil($semua->count() / $perHalaman));
        $hal = min(max(1, (int) $r->query('page', 1)), $halTerakhir);
        $items = $semua->forPage($hal, $perHalaman)->values();
        $paginator = new LengthAwarePaginator($items, $semua->count(), $perHalaman, $hal, ['path' => $r->url(), 'query' => $r->except('page')]);

        $nominal = BudgetBulan::where('tahun', $tahun)->whereIn('item_id', $items->pluck('id'))->get()
            ->groupBy('item_id')->map(fn ($g) => $g->pluck('nominal', 'bulan'));

        $bolehIsi = [];
        $bolehUbah = [];
        foreach ($items as $i) {
            $bolehIsi[$i->id] = $i->masihBerlaku($tahun) && BudgetPeran::bisaIsiBudget($i, $tahun, $user);
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
        if (in_array($peran, [BudgetPeran::ADMIN, BudgetPeran::DEPT_HEAD, BudgetPeran::PIC], true)) {
            $perTahun = [];
            $gab = collect();
            foreach (BudgetTahun::daftar() as $t) {
                $it = BudgetItem::terlihatOleh($user)->masihBerlakuDi((int) $t)->with('sub.kategori')->get();
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
            'tampilNonaktif' => $tampilNonaktif,
            'bisaSalin' => in_array($peran, [BudgetPeran::ADMIN, BudgetPeran::DEPT_HEAD, BudgetPeran::PIC], true),
            'bisaBuatTahun' => in_array($peran, [BudgetPeran::ADMIN, BudgetPeran::DEPT_HEAD, BudgetPeran::PIC], true),
            'dataSalin' => $dataSalin,
            'nominal' => $nominal,
            'itemAdaData' => BudgetBulan::where('tahun', $tahun)->whereIn('item_id', $items->pluck('id'))->pluck('item_id')
                ->merge(BudgetTagihan::where('tahun', $tahun)->whereIn('item_id', $items->pluck('id'))->pluck('item_id'))
                ->unique()->mapWithKeys(fn ($id) => [(int) $id => true])->all(),
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
            'filter' => ['bu' => $r->query('bu'), 'pt' => $r->query('pt'), 'kategori_id' => $r->query('kategori_id'), 'sub_id' => $r->query('sub_id'), 'q' => $r->query('q'), 'nonaktif' => $tampilNonaktif ? '1' : null],
        ]);
    }

    public function simpanItem(SimpanItemRequest $r)
    {
        $data = Arr::except($r->validated(), ['id', 'mode', 'tahun_berlaku', 'tahun', 'aktif']);
        $data['pic_user_id'] = $r->filled('pic_user_id') ? (int) $r->input('pic_user_id') : null;
        $data['dept_head_user_id'] = BudgetPeran::tentukanKepala($r->user(), $r->input('dept_head_user_id'));

        if ($r->filled('id')) {
            $item = BudgetItem::findOrFail($r->input('id'));

            // Ganti nama / pindah subkategori mulai tahun halaman ini: item lama tetap utuh untuk tahun-tahun sebelumnya.
            $mulai = (int) $r->input('tahun_berlaku');
            if ($r->input('mode') === 'mulai' && $mulai > 0
                && $item->tahunTerdaftar()->where('tahun', '<', $mulai)->exists()) {
                return $this->itemMulaiTahun($r, $item, $data, $mulai);
            }

            $sebelum = $item->getAttributes();
            $item->update($data);
            BudgetCatat::tulis('ubah', 'item', $item->id, $item->nama, $sebelum, $item->getAttributes(), ['item_id' => $item->id]);
            return back()->with('ok', 'Item diperbarui di semua tahun.');
        }

        // Item baru: langsung terdaftar dan aktif di tahun halaman yang sedang dibuka.
        $tahun = (int) ($r->input('tahun') ?: BudgetTahun::pilih(null));
        $baru = BudgetItem::create($data + ['aktif' => true]);
        $baru->daftarkanDi($tahun);
        BudgetCatat::tulis('tambah', 'item', $baru->id, $baru->nama, null, $baru->getAttributes(), ['item_id' => $baru->id, 'tahun' => $tahun]);
        return back()->with('ok', 'Item baru ditambahkan di tahun ' . $tahun . '. Untuk memakainya di tahun lain, salin dari tahun ini atau buat tahun barunya.');
    }

    /**
     * Item lama berhenti dipakai mulai tahun "mulai" (tidak lagi terdaftar di sana), item baru (nama/sub/detail baru) terdaftar sejak "mulai".
     * Angka budget, tagihan, dan Form Realisasi milik item lama untuk tahun >= mulai ikut pindah ke item baru;
     * tahun sebelumnya tidak disentuh, jadi laporan tahun lama tetap memakai nama lama dan tidak ada data yang hilang.
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

        $formPindah = 0;
        $baru = DB::transaction(function () use ($lama, $data, $mulai, &$formPindah) {
            $baru = BudgetItem::create($data + ['aktif' => true, 'item_asal_id' => $lama->id]);
            BudgetBulan::where('item_id', $lama->id)->where('tahun', '>=', $mulai)->update(['item_id' => $baru->id]);
            BudgetTagihan::where('item_id', $lama->id)->where('tahun', '>=', $mulai)->update(['item_id' => $baru->id]);

            // Tahun yang sebelumnya terdaftar untuk item lama (>= mulai) berpindah ke item baru, status aktifnya ikut.
            $baris = BudgetItemTahun::where('item_id', $lama->id)->where('tahun', '>=', $mulai)->get();
            foreach ($baris as $b) {
                $baru->daftarkanDi((int) $b->tahun, (bool) $b->aktif);
            }
            $baru->daftarkanDi($mulai);
            BudgetItemTahun::where('item_id', $lama->id)->where('tahun', '>=', $mulai)->delete();

            // Form Realisasi ikut menunjuk item baru (isian bertanggal sebelum "mulai" tetap bisa dicatat ke item lama lewat BudgetItem::untukTahun).
            $formPindah = BudgetForm::where('item_id', $lama->id)->update(['item_id' => $baru->id]);
            return $baru;
        });
        BudgetForm::lupakanKunci();

        BudgetCatat::tulis('pindah', 'item', $baru->id,
            'Item "' . $lama->nama . '" diganti "' . $baru->nama . '" mulai ' . $mulai,
            ['item_lama' => $lama->id],
            ['item_baru' => $baru->id, 'mulai' => $mulai, 'tahun_dipindah' => $tahunData->sort()->values()->all(), 'form_dipindah' => $formPindah],
            ['item_id' => $baru->id, 'tahun' => $mulai]);

        return redirect()->route('budgeting.budget.index', ['tahun' => $mulai])
            ->with('ok', 'Item "' . $baru->nama . '" dipakai mulai ' . $mulai . '. Tahun sebelum itu tetap memakai "' . $lama->nama . '".'
                . ($formPindah ? ' ' . $formPindah . ' Form Realisasi ikut pindah ke item baru.' : ''));
    }

    /**
     * Aktifkan / nonaktifkan item HANYA di satu tahun. Tahun lain tidak berubah, data tidak dihapus.
     * Item nonaktif tidak bisa diisi budget maupun realisasi baru di tahun itu; data yang sudah ada tetap tampil.
     */
    public function ubahStatus(Request $r, BudgetItem $item)
    {
        $d = $r->validate([
            'tahun' => ['required', 'integer', 'exists:budget_tahun,tahun'],
            'aktif' => ['required', 'boolean'],
        ]);
        abort_unless(BudgetPeran::bisaUbahItem($item, $r->user()), 403);

        $tahun = (int) $d['tahun'];
        $aktif = (bool) $d['aktif'];

        if (BudgetTahun::terkunci($tahun) && !BudgetPeran::isAdmin($r->user())) {
            return back()->withErrors(['budget' => 'Budget bulanan tahun ' . $tahun . ' sedang dikunci, jadi status item tidak bisa diubah. Minta Admin membuka kunci dulu.']);
        }

        $sebelum = ['tahun' => $tahun, 'aktif' => $item->masihBerlaku($tahun)];
        $item->aturAktifDi($tahun, $aktif);
        BudgetCatat::tulis($aktif ? 'aktifkan' : 'nonaktifkan', 'item', $item->id, $item->nama . ' (' . $tahun . ')', $sebelum, ['tahun' => $tahun, 'aktif' => $aktif], ['item_id' => $item->id, 'tahun' => $tahun]);

        return back()->with('ok', $aktif
            ? 'Item "' . $item->nama . '" diaktifkan lagi di tahun ' . $tahun . '.'
            : 'Item "' . $item->nama . '" dinonaktifkan di tahun ' . $tahun . ' saja. Tahun lain tidak berubah, dan data tahun ' . $tahun . ' tetap tersimpan.');
    }

    public function simpanBulan(SimpanBudgetBulanRequest $r)
    {
        $item = BudgetItem::findOrFail($r->input('item_id'));
        $tahun = (int) $r->input('tahun');
        $user = $r->user();

        if (!BudgetPeran::bisaIsiBudget($item, $tahun, $user)) {
            $pesan = BudgetPeran::bisaLihat($item, $user) && BudgetTahun::terkunci($tahun)
                ? 'Budget bulanan tahun ' . $tahun . ' sudah dikunci. Hubungi Admin untuk membuka kunci.'
                : 'Anda tidak berhak mengisi budget item ini. Item ini bukan PIC Anda, hubungi Dept Head Anda.';
            return $this->gagal($r, $pesan);
        }

        // Aturan tahun di server: item harus terdaftar dan aktif di tahun yang diisi
        if (!$item->masihBerlaku($tahun)) {
            return $this->gagal($r, 'Item "' . $item->nama . '" tidak aktif di tahun ' . $tahun . ', jadi budgetnya tidak bisa diubah. Aktifkan dulu di Rencana Budget ' . $tahun . ' (tombol "Aktifkan di ' . $tahun . '").');
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

        $pesan = 'Budget "' . $item->nama . '" tahun ' . $tahun . ' disimpan.';
        if ($r->expectsJson()) {
            return response()->json(['ok' => true, 'pesan' => $pesan, 'total' => array_sum($sesudah)]);
        }
        return back()->with('ok', $pesan);
    }

    /** Penolakan: JSON untuk simpan per baris (tanpa muat ulang halaman), halaman biasa untuk form lain. */
    protected function gagal(Request $r, string $pesan)
    {
        return $r->expectsJson()
            ? response()->json(['ok' => false, 'pesan' => $pesan], 422)
            : back()->withErrors(['budget' => $pesan]);
    }

    /** Admin, Dept Head, dan PIC: buat tahun anggaran baru, sekaligus (opsional) mengisinya dari tahun lain. */
    public function buatTahun(Request $r)
    {
        $d = $r->validate([
            'tahun' => ['required', 'integer', 'between:2000,2100'],
            'dari' => ['nullable', 'integer', 'exists:budget_tahun,tahun'],
            'angka' => ['nullable', 'boolean'],
        ]);
        $tahun = (int) $d['tahun'];
        $dari = !empty($d['dari']) ? (int) $d['dari'] : null;
        if ($dari === $tahun) {
            return back()->withErrors(['budget' => 'Tahun asal dan tahun tujuan tidak boleh sama.']);
        }

        $row = BudgetTahun::firstOrCreate(['tahun' => $tahun]);
        $baru = $row->wasRecentlyCreated;
        if ($baru) {
            BudgetTahun::lupakanDaftar();
            BudgetCatat::tulis('tambah', 'tahun', $row->id, 'Tahun anggaran ' . $tahun, null, $row->getAttributes(), ['tahun' => $tahun]);
        } elseif (!$dari) {
            return back()->withErrors(['budget' => 'Tahun ' . $tahun . ' sudah ada. Pilih "Isi dari tahun" bila ingin mengisinya dari tahun lain.']);
        }
        if (!$baru && $row->terkunci && !BudgetPeran::isAdmin($r->user())) {
            return back()->withErrors(['budget' => 'Tahun ' . $tahun . ' sedang dikunci.']);
        }

        $pesan = $baru ? 'Tahun ' . $tahun . ' dibuat.' : 'Tahun ' . $tahun . ' sudah ada.';
        if ($dari) {
            $h = BudgetSalin::jalankan($dari, $tahun, $r->user(), false, [], $r->boolean('angka', true));
            $pesan .= ' ' . $h['item'] . ' item aktif dari ' . $dari . ' dipasang di ' . $tahun
                . ($r->boolean('angka', true) ? ' beserta ' . $h['baris'] . ' angka budget bulanan' : ' tanpa angka') . '.'
                . ' Nonaktifkan item yang tidak dipakai di ' . $tahun . ' lewat tombol "Nonaktifkan" pada barisnya.';
        } else {
            $pesan .= ' Masih kosong: pakai "Item baru" atau "Salin dari tahun lain".';
        }
        return redirect()->route('budgeting.budget.index', ['tahun' => $tahun])->with('ok', $pesan);
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
            return back()->withErrors(['budget' => 'Tidak ada yang disalin. Item yang cocok sudah ada di tahun ' . $ke . ', atau tidak ada item aktif yang cocok dengan pilihan Anda di tahun ' . $r->input('dari') . '.']);
        }
        return redirect()->route('budgeting.budget.index', ['tahun' => $ke])
            ->with('ok', $hasil['item'] . ' item baru dan ' . $hasil['baris'] . ' baris budget bulanan disalin dari ' . $r->input('dari') . ' ke ' . $ke . '.');
    }
}
