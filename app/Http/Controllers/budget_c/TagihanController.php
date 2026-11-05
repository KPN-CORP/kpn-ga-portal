<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Http\Requests\budget_r\SimpanTagihanRequest;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetTagihan;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Http\Request;

class TagihanController extends Controller
{
    public function index(Request $r)
    {
        $user = $r->user();
        $tahun = BudgetTahun::pilih($r->query('tahun'));

        $semuaItem = BudgetItem::terlihatOleh($user)->where('aktif', true)->with('sub.kategori')->orderBy('nama')->get();
        $itemBisaInput = $semuaItem->filter(fn ($i) => BudgetPeran::bisaInputTagihan($i, $user))->values();

        // Kategori diambil dari relasi yang sudah di-load (tanpa query tambahan)
        $kategori = $semuaItem->pluck('sub.kategori')->filter()->unique('id')->sortBy('nama')->values();

        $q = BudgetTagihan::with(['item.sub.kategori', 'penginput'])
            ->whereIn('item_id', BudgetItem::terlihatOleh($user)->select('id'))
            ->where('tahun', $tahun)
            ->when($r->query('bulan'), fn ($x, $v) => $x->where('bulan', $v))
            ->when($r->query('item_id'), fn ($x, $v) => $x->where('item_id', $v));

        $ringkas = [
            'tagihan' => (float) (clone $q)->sum('tagihan'),
            'ppn' => (float) (clone $q)->sum('ppn'),
            'biaya_materai' => (float) (clone $q)->sum('biaya_materai'),
            'total' => (float) (clone $q)->sum('total'),
        ];

        return view('budget_p.tagihan.index', [
            'tahun' => $tahun,
            'daftarTahun' => BudgetTahun::daftar(),
            'tahunRow' => BudgetTahun::where('tahun', $tahun)->first(),
            'semuaItem' => $semuaItem,
            'itemBisaInput' => $itemBisaInput,
            'kategori' => $kategori,
            'daftar' => $q->orderByDesc('bulan')->orderByDesc('id')->paginate(25)->withQueryString(),
            'ringkas' => $ringkas,
            'filter' => ['bulan' => $r->query('bulan'), 'item_id' => $r->query('item_id')],
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

        if ($r->filled('id')) {
            $baris = BudgetTagihan::with('item')->findOrFail($r->input('id'));
            abort_unless(BudgetPeran::bisaInputTagihan($baris->item, $user), 403);
            $baris->update($data);
            return back()->with('ok', 'Tagihan diperbarui.');
        }

        BudgetTagihan::create($data + ['input_oleh' => $user->id]);
        return back()->with('ok', 'Tagihan disimpan.');
    }

    public function hapus(Request $r, BudgetTagihan $tagihan)
    {
        abort_unless(BudgetPeran::bisaInputTagihan($tagihan->item, $r->user()), 403);
        $tagihan->delete();
        return back()->with('ok', 'Tagihan dihapus.');
    }
}