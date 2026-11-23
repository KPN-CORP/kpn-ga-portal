<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Http\Requests\budget_r\SimpanKategoriRequest;
use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetPicHead;
use App\Models\budget_m\BudgetSub;
use App\Models\budget_m\BudgetTahun;
use App\Models\User;
use App\Support\budget_s\BudgetKunci;
use App\Support\budget_s\BudgetCatat;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Http\Request;

/** Master tahun, kategori, subkategori. Tahun/kunci/ubah/hapus: Admin (dijaga di route). Tambah kategori/sub: semua peran. */
class MasterController extends Controller
{
    public function index(Request $r)
    {
        $admin = BudgetPeran::isAdmin(auth()->user());
        $tahun = $admin ? BudgetTahun::orderByDesc('tahun')->get() : collect();
        $pengunci = User::whereIn('id', $tahun->pluck('dikunci_oleh')->filter())->pluck('name', 'id');

        // Info per subkategori: di tahun mana item tampil (sama dengan yang tampil di halaman Budget) dan Dept Head pemilik item-nya
        $user = auth()->user();
        $daftarTahun = BudgetTahun::daftar();
        // Default tahun berjalan (atau tahun terbaru bila belum terdaftar); "semua" menampilkan semua tahun
        $tahunPilih = $r->query('tahun') === 'semua' ? null : BudgetTahun::pilih($r->query('tahun'));
        if ($tahunPilih !== null && !in_array($tahunPilih, $daftarTahun, true)) {
            $tahunPilih = BudgetTahun::pilih(null);
        }

        $tahunSub = [];   // sub_id => [tahun => jumlah item]
        $kepalaId = [];   // sub_id => [dept_head_id => true]
        foreach ($daftarTahun as $y) {
            if ($tahunPilih && $y !== $tahunPilih) {
                continue;
            }
            BudgetItem::terlihatOleh($user)->berlakuDi($y)->get(['id', 'sub_id', 'dept_head_user_id'])
                ->each(function ($i) use (&$tahunSub, &$kepalaId, $y) {
                    $tahunSub[$i->sub_id][$y] = ($tahunSub[$i->sub_id][$y] ?? 0) + 1;
                    if ($i->dept_head_user_id) {
                        $kepalaId[$i->sub_id][(int) $i->dept_head_user_id] = true;
                    }
                });
        }
        $kepalaSub = collect($kepalaId)->map(fn ($ids) => collect(array_keys($ids))->map(fn ($id) => BudgetPeran::namaDept($id))->unique()->sort()->values()->all())->all();

        // Urutan: nama Dept (A-Z), nama orangnya, lalu angka urut, baru nama kategori. Tanpa pemilik ("Umum") paling akhir.
        $kategori = BudgetKategori::terlihatOleh()->with(['subs', 'pemilik'])->get()
            ->sortBy(fn ($k) => $k->kunciUrut())->values();
        if ($tahunPilih) {
            // Hanya kategori dan subkategori yang punya item di tahun yang dipilih
            $kategori = $kategori->each(fn ($k) => $k->setRelation('subs', $k->subs->filter(fn ($sb) => isset($tahunSub[$sb->id]))->values()))
                ->filter(fn ($k) => $k->subs->isNotEmpty())->values();
        }

        return view('budget_p.master.index', [
            'tahunList' => $tahun,
            'pengunci' => $pengunci,
            'kategori' => $kategori,
            'pilihanPemilik' => $this->pilihanPemilik(),
            'tahunSub' => $tahunSub,
            'kepalaSub' => $kepalaSub,
            'daftarTahun' => $daftarTahun,
            'tahunPilih' => $tahunPilih,
        ]);
    }

    public function tambahTahun(Request $r)
    {
        $r->validate(['tahun' => ['required', 'integer', 'between:2000,2100', 'unique:budget_tahun,tahun']]);
        $baru = BudgetTahun::create(['tahun' => (int) $r->input('tahun')]);
        BudgetTahun::lupakanDaftar();
        BudgetCatat::tulis('tambah', 'tahun', $baru->id, 'Tahun anggaran ' . $baru->tahun, null, $baru->getAttributes(), ['tahun' => (int) $baru->tahun]);
        return back()->with('ok', 'Tahun anggaran ' . $r->input('tahun') . ' ditambahkan.');
    }

    public function kunci(Request $r, int $tahun)
    {
        $row = BudgetTahun::where('tahun', $tahun)->first();
        abort_unless($row, 404);
        $sebelum = $row->getAttributes();
        BudgetKunci::kunci($tahun, $r->user());
        BudgetCatat::tulis('kunci', 'tahun', $row->id, 'Kunci tahun ' . $tahun, $sebelum, $row->fresh()->getAttributes(), ['tahun' => $tahun]);
        return back()->with('ok', 'Tahun ' . $tahun . ' dikunci. Dept Head dan PIC tidak bisa lagi mengubah budget bulanan.');
    }

    public function buka(int $tahun)
    {
        $row = BudgetTahun::where('tahun', $tahun)->first();
        abort_unless($row, 404);
        $sebelum = $row->getAttributes();
        BudgetKunci::buka($tahun);
        BudgetCatat::tulis('buka', 'tahun', $row->id, 'Buka kunci tahun ' . $tahun, $sebelum, $row->fresh()->getAttributes(), ['tahun' => $tahun]);
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

            if ($r->filled('id')) {
                $k = BudgetKategori::findOrFail($r->input('id'));
                $sebelum = $k->getAttributes();
                $k->update($data);
                BudgetCatat::tulis('ubah', 'kategori', $k->id, $k->nama, $sebelum, $k->getAttributes());
            } else {
                $k = BudgetKategori::create($data);
                BudgetCatat::tulis('tambah', 'kategori', $k->id, $k->nama, null, $k->getAttributes());
            }
            return back()->with('ok', 'Kategori disimpan.');
        }

        $data['kategori_id'] = (int) $r->input('kategori_id');
        abort_unless(BudgetKategori::terlihatOleh($user)->whereKey($data['kategori_id'])->exists(), 403);

        if ($r->filled('id')) {
            $s = BudgetSub::findOrFail($r->input('id'));
            $sebelum = $s->getAttributes();
            $s->update($data);
            BudgetCatat::tulis('ubah', 'sub', $s->id, $s->nama, $sebelum, $s->getAttributes());
        } else {
            $s = BudgetSub::create($data);
            BudgetCatat::tulis('tambah', 'sub', $s->id, $s->nama, null, $s->getAttributes());
        }
        return back()->with('ok', 'Subkategori disimpan.');
    }

    public function hapusKategori(BudgetKategori $kategori)
    {
        if ($kategori->subs()->exists()) {
            return back()->withErrors(['master' => 'Kategori masih punya subkategori. Hapus atau nonaktifkan saja.']);
        }
        $sebelum = $kategori->getAttributes();
        $kategori->delete();
        BudgetCatat::tulis('hapus', 'kategori', $kategori->id, $kategori->nama, $sebelum, null);
        return back()->with('ok', 'Kategori dihapus.');
    }

    public function hapusSub(BudgetSub $sub)
    {
        if ($sub->items()->exists()) {
            return back()->withErrors(['master' => 'Subkategori masih dipakai item. Nonaktifkan saja.']);
        }
        $sebelum = $sub->getAttributes();
        $sub->delete();
        BudgetCatat::tulis('hapus', 'sub', $sub->id, $sub->nama, $sebelum, null);
        return back()->with('ok', 'Subkategori dihapus.');
    }
}