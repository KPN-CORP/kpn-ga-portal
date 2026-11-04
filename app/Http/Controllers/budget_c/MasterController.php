<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Http\Requests\budget_r\SimpanKategoriRequest;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetSub;
use App\Models\budget_m\BudgetTahun;
use App\Models\User;
use App\Support\budget_s\BudgetKunci;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Http\Request;

/** Master tahun, kategori, subkategori. Tahun/kunci/ubah/hapus: Admin (dijaga di route). Tambah kategori/sub: semua peran. */
class MasterController extends Controller
{
    public function index()
    {
        $admin = BudgetPeran::isAdmin(auth()->user());
        $tahun = $admin ? BudgetTahun::orderByDesc('tahun')->get() : collect();
        $pengunci = User::whereIn('id', $tahun->pluck('dikunci_oleh')->filter())->pluck('name', 'id');

        return view('budget_p.master.index', [
            'tahunList' => $tahun,
            'pengunci' => $pengunci,
            'kategori' => BudgetKategori::with('subs')->orderBy('urut')->orderBy('nama')->get(),
        ]);
    }

    public function tambahTahun(Request $r)
    {
        $r->validate(['tahun' => ['required', 'integer', 'between:2000,2100', 'unique:budget_tahun,tahun']]);
        BudgetTahun::create(['tahun' => (int) $r->input('tahun')]);
        return back()->with('ok', 'Tahun anggaran ' . $r->input('tahun') . ' ditambahkan.');
    }

    public function kunci(Request $r, int $tahun)
    {
        abort_unless(BudgetTahun::where('tahun', $tahun)->exists(), 404);
        BudgetKunci::kunci($tahun, $r->user());
        return back()->with('ok', 'Tahun ' . $tahun . ' dikunci. Dept Head dan PIC tidak bisa lagi mengubah budget bulanan.');
    }

    public function buka(int $tahun)
    {
        abort_unless(BudgetTahun::where('tahun', $tahun)->exists(), 404);
        BudgetKunci::buka($tahun);
        return back()->with('ok', 'Kunci tahun ' . $tahun . ' dibuka.');
    }

    public function simpanKategori(SimpanKategoriRequest $r)
    {
        $admin = BudgetPeran::isAdmin($r->user());
        $data = [
            'nama' => $r->input('nama'),
            'urut' => (int) $r->input('urut', 0),
            // Non-Admin hanya menambah baru, selalu aktif
            'aktif' => $admin ? $r->boolean('aktif') : true,
        ];

        if ($r->input('jenis') === 'kategori') {
            $r->filled('id') ? BudgetKategori::findOrFail($r->input('id'))->update($data) : BudgetKategori::create($data);
            return back()->with('ok', 'Kategori disimpan.');
        }

        $data['kategori_id'] = (int) $r->input('kategori_id');
        $r->filled('id') ? BudgetSub::findOrFail($r->input('id'))->update($data) : BudgetSub::create($data);
        return back()->with('ok', 'Subkategori disimpan.');
    }

    public function hapusKategori(BudgetKategori $kategori)
    {
        if ($kategori->subs()->exists()) {
            return back()->withErrors(['master' => 'Kategori masih punya subkategori. Hapus atau nonaktifkan saja.']);
        }
        $kategori->delete();
        return back()->with('ok', 'Kategori dihapus.');
    }

    public function hapusSub(BudgetSub $sub)
    {
        if ($sub->items()->exists()) {
            return back()->withErrors(['master' => 'Subkategori masih dipakai item. Nonaktifkan saja.']);
        }
        $sub->delete();
        return back()->with('ok', 'Subkategori dihapus.');
    }
}