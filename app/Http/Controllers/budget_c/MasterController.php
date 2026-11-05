<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Http\Requests\budget_r\SimpanKategoriRequest;
use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetPicHead;
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
            'kategori' => BudgetKategori::terlihatOleh()->with(['subs', 'pemilik'])->orderBy('urut')->orderBy('nama')->get(),
            'pilihanPemilik' => $this->pilihanPemilik(),
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

    /** Dept Head yang boleh jadi pemilik kategori baru, menurut peran pengguna: id => nama. */
    protected function pilihanPemilik(): \Illuminate\Support\Collection
    {
        $user = auth()->user();
        $peran = BudgetPeran::peran($user);

        $ids = match ($peran) {
            BudgetPeran::ADMIN => BudgetAkses::where('peran', BudgetPeran::DEPT_HEAD)->where('aktif', true)->pluck('user_id'),
            BudgetPeran::DEPT_HEAD => collect([$user->id]),
            BudgetPeran::PIC => BudgetPicHead::where('pic_user_id', $user->id)->where('aktif', true)->pluck('dept_head_user_id'),
            default => collect(),
        };

        return User::whereIn('id', $ids)->orderBy('name')->get()->mapWithKeys(fn ($u) => [$u->id => BudgetPeran::namaDeptLengkap($u->id)]);
    }

    /** Pemilik kategori: Admin memilih (kosong = umum), Dept Head dirinya, PIC salah satu Dept Head-nya. */
    protected function tentukanPemilik($r): ?int
    {
        $pilihan = $this->pilihanPemilik();
        $in = $r->filled('dept_head_user_id') ? (int) $r->input('dept_head_user_id') : null;

        if (BudgetPeran::isAdmin($r->user())) {
            abort_if($in !== null && !$pilihan->has($in), 422, 'Dept Head tidak valid.');
            return $in;
        }
        if ($in === null && $pilihan->count() === 1) {
            return (int) $pilihan->keys()->first();
        }
        abort_unless($in !== null && $pilihan->has($in), 403);
        return $in;
    }

    public function simpanKategori(SimpanKategoriRequest $r)
    {
        $user = $r->user();
        $admin = BudgetPeran::isAdmin($user);
        $data = [
            'nama' => $r->input('nama'),
            'urut' => (int) $r->input('urut', 0),
            // Non-Admin hanya menambah baru, selalu aktif
            'aktif' => $admin ? $r->boolean('aktif') : true,
        ];

        if ($r->input('jenis') === 'kategori') {
            $data['dept_head_user_id'] = $this->tentukanPemilik($r);

            $kembar = BudgetKategori::where('nama', $data['nama'])
                ->where(fn ($q) => $data['dept_head_user_id']
                    ? $q->where('dept_head_user_id', $data['dept_head_user_id'])
                    : $q->whereNull('dept_head_user_id'))
                ->when($r->filled('id'), fn ($q) => $q->where('id', '<>', $r->input('id')))
                ->exists();
            if ($kembar) {
                return back()->withErrors(['master' => 'Kategori dengan nama itu sudah ada pada Dept Head yang sama.']);
            }

            $r->filled('id') ? BudgetKategori::findOrFail($r->input('id'))->update($data) : BudgetKategori::create($data);
            return back()->with('ok', 'Kategori disimpan.');
        }

        $data['kategori_id'] = (int) $r->input('kategori_id');
        abort_unless(BudgetKategori::terlihatOleh($user)->whereKey($data['kategori_id'])->exists(), 403);

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