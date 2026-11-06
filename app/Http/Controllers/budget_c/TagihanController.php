<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Http\Requests\budget_r\SimpanTagihanRequest;
use App\Models\budget_m\BudgetBulan;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetTagihan;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetCatat;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Http\Request;

class TagihanController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();
        $tahun = BudgetTahun::pilih($r->query('tahun'));

        $semuaItem = BudgetItem::terlihatOleh($user)->berlakuDi($tahun)->with('sub.kategori')->orderBy('nama')->get();
        $itemBisaInput = $semuaItem->filter(fn ($i) => BudgetPeran::bisaInputTagihan($i, $user))->values();

        // Untuk form Catat realisasi: item yang bisa dicatat user di setiap tahun (kolom Tahun di form bisa diganti)
        $daftarTahun = BudgetTahun::daftar();
        $perTahun = [];
        $gabungan = collect();
        foreach ($daftarTahun as $t) {
            $it = (int) $t === $tahun ? $semuaItem : BudgetItem::terlihatOleh($user)->berlakuDi((int) $t)->with('sub.kategori')->get();
            $perTahun[(int) $t] = $it->pluck('id')->all();
            $gabungan = $gabungan->concat($it);
        }
        $gabungan = $gabungan->unique('id')->values();
        $izin = $gabungan->filter(fn ($i) => BudgetPeran::bisaInputTagihan($i, $user))->pluck('id')->flip();
        $itemModal = $gabungan->filter(fn ($i) => $izin->has($i->id))->values();
        $idPerTahun = collect($perTahun)->map(fn ($ids) => array_values(array_filter($ids, fn ($id) => $izin->has($id))))->all();

        // Kategori diambil dari relasi yang sudah di-load (tanpa query tambahan)
        $kategori = $semuaItem->pluck('sub.kategori')->filter()->unique('id')->sortBy('nama')->values();

        $q = BudgetTagihan::with(['item.sub.kategori', 'penginput'])
            ->whereIn('item_id', BudgetItem::terlihatOleh($user)->select('id'))
            ->where('tahun', $tahun)
            ->when($r->query('bulan'), fn ($x, $v) => $x->where('bulan', $v))
            ->when($r->query('kategori_id'), fn ($x, $v) => $x->whereHas('item.sub', fn ($s) => $s->where('kategori_id', $v)))
            ->when($r->query('sub_id'), fn ($x, $v) => $x->whereHas('item', fn ($i) => $i->where('sub_id', $v)))
            ->when($r->query('item_id'), fn ($x, $v) => $x->where('item_id', $v))
            ->when(trim((string) $r->query('q')) !== '', function ($x) use ($r) {
                $v = '%' . trim((string) $r->query('q')) . '%';
                $x->where(fn ($w) => $w->where('keterangan', 'like', $v)
                    ->orWhereHas('item', fn ($i) => $i->where('nama', 'like', $v)
                        ->orWhereHas('sub', fn ($s) => $s->where('nama', 'like', $v)
                            ->orWhereHas('kategori', fn ($k) => $k->where('nama', 'like', $v)))));
            });

        $ringkas = [
            'tagihan' => (float) (clone $q)->sum('tagihan'),
            'ppn' => (float) (clone $q)->sum('ppn'),
            'biaya_materai' => (float) (clone $q)->sum('biaya_materai'),
            'total' => (float) (clone $q)->sum('total'),
        ];

        $perHalaman = in_array((int) $r->query('per'), [25, 50, 100], true) ? (int) $r->query('per') : 25;
        $daftar = $q->orderByDesc('bulan')->orderByDesc('id')->paginate($perHalaman)->withQueryString();

        // Item + bulan yang punya lebih dari satu tagihan (supaya kelihatan kalau terinput dobel)
        $jumlahPerBulan = $daftar->isEmpty() ? collect() : BudgetTagihan::where('tahun', $tahun)
            ->whereIn('item_id', $daftar->pluck('item_id')->unique())
            ->selectRaw('item_id, bulan, COUNT(*) AS c')->groupBy('item_id', 'bulan')->get()
            ->mapWithKeys(fn ($b) => [$b->item_id . '-' . $b->bulan => (int) $b->c]);

        return view('budget_p.tagihan.index', [
            'tahun' => $tahun,
            'daftarTahun' => $daftarTahun,
            'itemModal' => $itemModal,
            'idPerTahun' => $idPerTahun,
            'tahunRow' => BudgetTahun::where('tahun', $tahun)->first(),
            'semuaItem' => $semuaItem,
            'itemBisaInput' => $itemBisaInput,
            'kategori' => $kategori,
            'daftar' => $daftar,
            'perHalaman' => $perHalaman,
            'jumlahPerBulan' => $jumlahPerBulan,
            'ringkas' => $ringkas,
            'subList' => $semuaItem->pluck('sub')->filter()->unique('id')
                ->sortBy(fn ($x) => mb_strtolower($x->kategori->nama . ' ' . $x->nama))->values(),
            'filter' => [
                'bulan' => $r->query('bulan'), 'kategori_id' => $r->query('kategori_id'),
                'sub_id' => $r->query('sub_id'), 'item_id' => $r->query('item_id'), 'q' => trim((string) $r->query('q')),
            ],
        ]);
    }

    /** Tagihan yang sudah tercatat untuk item + bulan + tahun, beserta budget bulan itu (untuk panel di form Catat realisasi). */
    public function cek(Request $r)
    {
        $d = $r->validate([
            'item_id' => ['required', 'integer'],
            'tahun' => ['required', 'integer'],
            'bulan' => ['required', 'integer', 'between:1,12'],
        ]);
        $item = BudgetItem::findOrFail($d['item_id']);
        abort_unless(BudgetPeran::bisaInputTagihan($item, $r->user()), 403);

        $baris = BudgetTagihan::with('penginput')
            ->where('item_id', $item->id)->where('tahun', $d['tahun'])->where('bulan', $d['bulan'])
            ->orderBy('id')->get();

        return response()->json([
            'budget_bulan' => (float) BudgetBulan::where('item_id', $item->id)->where('tahun', $d['tahun'])->where('bulan', $d['bulan'])->value('nominal'),
            'rows' => $baris->map(fn ($t) => [
                'id' => $t->id,
                'tagihan' => (float) $t->tagihan,
                'ppn' => (float) $t->ppn,
                'biaya_materai' => (float) $t->biaya_materai,
                'total' => (float) $t->total,
                'keterangan' => $t->keterangan,
                'oleh' => $t->penginput->name ?? '-',
                'dibuat' => optional($t->created_at)->format('d/m/Y H:i'),
                'diubah' => ($t->updated_at && $t->created_at && $t->updated_at->diffInSeconds($t->created_at) > 60) ? $t->updated_at->format('d/m/Y H:i') : null,
            ])->values(),
        ]);
    }

    public function simpan(SimpanTagihanRequest $r)
    {
        $user = $r->user();
        $item = BudgetItem::findOrFail($r->input('item_id'));
        abort_unless(BudgetPeran::bisaInputTagihan($item, $user), 403);

        $tagihan = (float) $r->input('tagihan');
        $ppn = (float) $r->input('ppn', 0);
        $materai = (float) $r->input('biaya_materai', 0);

        $data = [
            'item_id' => $item->id,
            'tahun' => (int) $r->input('tahun'),
            'bulan' => (int) $r->input('bulan'),
            'tagihan' => $tagihan,
            'ppn' => $ppn,
            'biaya_materai' => $materai,
            'total' => $tagihan + $ppn + $materai,
            'keterangan' => $r->input('keterangan'),
        ];

        $tahunHalaman = (int) $r->input('tahun_halaman', $data['tahun']);
        $keTahun = fn () => $tahunHalaman !== $data['tahun']
            ? redirect()->route('budgeting.tagihan.index', ['tahun' => $data['tahun']])
            : back();

        if ($r->filled('id')) {
            $baris = BudgetTagihan::with('item')->findOrFail($r->input('id'));
            abort_unless(BudgetPeran::bisaInputTagihan($baris->item, $user), 403);
            $sebelum = $baris->getAttributes();
            $baris->update($data);
            BudgetCatat::tulis('ubah', 'tagihan', $baris->id, $item->nama, $sebelum, $baris->getAttributes(), ['item_id' => $baris->item_id, 'tahun' => $baris->tahun]);
            return $keTahun()->with('ok', 'Tagihan diperbarui' . ($tahunHalaman !== $data['tahun'] ? ' (tahun ' . $data['tahun'] . ')' : '') . '.');
        }

        $baru = BudgetTagihan::create($data + ['input_oleh' => $user->id]);
        BudgetCatat::tulis('tambah', 'tagihan', $baru->id, $item->nama, null, $baru->getAttributes(), ['item_id' => $baru->item_id, 'tahun' => $baru->tahun]);
        return $keTahun()->with('ok', 'Tagihan disimpan' . ($tahunHalaman !== $data['tahun'] ? ' (tahun ' . $data['tahun'] . ')' : '') . '.');
    }

    public function hapus(Request $r, BudgetTagihan $tagihan)
    {
        abort_unless(BudgetPeran::bisaInputTagihan($tagihan->item, $r->user()), 403);
        $sebelum = $tagihan->getAttributes();
        $tagihan->delete();
        BudgetCatat::tulis('hapus', 'tagihan', $tagihan->id, $tagihan->item->nama ?? null, $sebelum, null, ['item_id' => $tagihan->item_id, 'tahun' => $tagihan->tahun]);
        return back()->with('ok', 'Tagihan dihapus.');
    }
}