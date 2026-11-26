<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetPicHead;
use App\Models\budget_m\BudgetPicTugas;
use App\Models\budget_m\BudgetSub;
use App\Support\budget_s\BudgetCatat;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Tugas PIC: Dept Head (dan Admin) menentukan PIC mana yang memproses kategori, subkategori, dan item mana.
 * Bila saklar "Batasi PIC" aktif, PIC hanya melihat dan mengerjakan bagian yang ditugaskan; Dept Head tetap melihat seluruh timnya.
 * Penugasan di kategori berlaku ke semua sub + item di bawahnya; di subkategori berlaku ke semua item di bawahnya.
 */
class TugasPicController extends Controller
{
    /** Dept Head yang sedang diatur: Dept Head = dirinya, Admin = pilihan (query "dept"). */
    protected function tentukanHead(Request $r)
    {
        $user = $r->user();
        if (BudgetPeran::isDeptHead($user)) {
            return (int) $user->id;
        }
        $daftar = BudgetAkses::where('peran', BudgetPeran::DEPT_HEAD)->where('aktif', true)->pluck('user_id')->map(fn ($v) => (int) $v);
        $pilih = (int) ($r->input('dept') ?: $r->query('dept'));
        return $daftar->contains($pilih) ? $pilih : (int) ($daftar->sortBy(fn ($id) => mb_strtolower(BudgetPeran::namaDept($id)))->first() ?? 0);
    }

    public function index(Request $r)
    {
        $user = $r->user();
        $admin = BudgetPeran::isAdmin($user);
        $siap = BudgetPeran::fiturPicAktif();
        $head = $this->tentukanHead($r);

        $kepalaList = $admin
            ? BudgetAkses::where('peran', BudgetPeran::DEPT_HEAD)->where('aktif', true)->pluck('user_id')
                ->mapWithKeys(fn ($id) => [(int) $id => BudgetPeran::namaDeptLengkap($id)])->sort()
            : collect();

        $picHead = $head ? BudgetPicHead::with('pic')->where('dept_head_user_id', $head)->where('aktif', true)->get()
            ->sortBy(fn ($p) => mb_strtolower($p->pic->name ?? ''))->values() : collect();
        $picId = (int) $r->query('pic');
        if (!$picHead->contains(fn ($p) => (int) $p->pic_user_id === $picId)) {
            $picId = (int) ($picHead->first()->pic_user_id ?? 0);
        }

        $batasi = $siap && $head ? BudgetPeran::batasiPic($head) : false;

        // Pohon kategori > sub > item milik Dept Head ini (kategori Umum hanya ditampilkan bagian yang berisi item Dept Head ini)
        $kategori = collect();
        $tugas = collect();
        if ($head) {
            $kategori = BudgetKategori::where(function ($q) use ($head) {
                $q->where('dept_head_user_id', $head)
                    ->orWhereHas('subs.items', fn ($i) => $i->where('dept_head_user_id', $head));
            })->with(['subs.items' => fn ($i) => $i->where('dept_head_user_id', $head)->aktifDiTahunMana()->orderBy('nama')])
                ->get()->sortBy(fn ($k) => $k->kunciUrut())->values();
            $kategori->each(function ($k) use ($head) {
                if (!$k->dept_head_user_id) {
                    $k->setRelation('subs', $k->subs->filter(fn ($s) => $s->items->isNotEmpty())->values());
                }
            });
            $kategori = $kategori->filter(fn ($k) => $k->subs->isNotEmpty() || (int) $k->dept_head_user_id === $head)->values();
            $tugas = $siap ? BudgetPicTugas::where('dept_head_user_id', $head)->get() : collect();
        }

        $nama = $picHead->mapWithKeys(fn ($p) => [(int) $p->pic_user_id => $p->pic->name ?? ('#' . $p->pic_user_id)]);
        $terpilih = ['kat' => [], 'sub' => [], 'item' => []];
        $lain = ['kat' => [], 'sub' => [], 'item' => []];   // jenis => id => [nama PIC lain]
        $ringkas = [];                                        // pic_id => [kat, sub, item]
        foreach ($tugas as $t) {
            $pid = (int) $t->pic_user_id;
            $jenis = $t->item_id ? 'item' : ($t->sub_id ? 'sub' : 'kat');
            $nid = (int) ($t->item_id ?: ($t->sub_id ?: $t->kategori_id));
            $ringkas[$pid][$jenis] = ($ringkas[$pid][$jenis] ?? 0) + 1;
            if ($pid === $picId) {
                $terpilih[$jenis][$nid] = true;
            } elseif ($nama->has($pid)) {
                $lain[$jenis][$nid][] = $nama[$pid];
            }
        }
        // PIC lama yang ditunjuk langsung di item (budget_item.pic_user_id) dihitung sebagai penugasan item
        if ($head) {
            BudgetItem::where('dept_head_user_id', $head)->whereNotNull('pic_user_id')->get(['id', 'pic_user_id'])->each(function ($i) use ($picId, $nama, &$terpilih, &$lain, &$ringkas) {
                $pid = (int) $i->pic_user_id;
                if (empty($terpilih['item'][$i->id]) && $pid === $picId) {
                    $terpilih['item'][(int) $i->id] = true;
                    $ringkas[$pid]['item'] = ($ringkas[$pid]['item'] ?? 0) + 1;
                } elseif ($pid !== $picId && $nama->has($pid)) {
                    $lain['item'][(int) $i->id][] = $nama[$pid];
                }
            });
        }

        return view('budget_p.tugas.index', compact(
            'admin', 'siap', 'head', 'kepalaList', 'picHead', 'picId', 'batasi', 'kategori', 'terpilih', 'lain', 'ringkas', 'nama'
        ));
    }

    /** Hidupkan / matikan pembatasan PIC untuk satu Dept Head. */
    public function mode(Request $r)
    {
        abort_unless(BudgetPeran::fiturPicAktif(), 409, 'Jalankan database/sql/2026_11_26_budget_pic_tugas.sql dulu.');
        $head = $this->tentukanHead($r);
        abort_unless($head, 404);

        $aktif = $r->boolean('batasi');
        $akses = BudgetAkses::where('user_id', $head)->where('peran', BudgetPeran::DEPT_HEAD)->firstOrFail();
        $sebelum = $akses->getAttributes();
        $akses->update(['batasi_pic' => $aktif]);
        BudgetPeran::lupakan($head);
        BudgetCatat::tulis('ubah', 'akses', $akses->id, 'Batasi PIC ' . ($aktif ? 'dihidupkan' : 'dimatikan') . ' untuk Dept user #' . $head, $sebelum, $akses->fresh()->getAttributes());

        $kosong = 0;
        if ($aktif) {
            $punya = BudgetPicTugas::where('dept_head_user_id', $head)->pluck('pic_user_id')
                ->merge(BudgetItem::where('dept_head_user_id', $head)->whereNotNull('pic_user_id')->pluck('pic_user_id'))->unique();
            $kosong = BudgetPicHead::where('dept_head_user_id', $head)->where('aktif', true)->whereNotIn('pic_user_id', $punya)->count();
        }

        return redirect()->route('budgeting.tugas', array_filter(['dept' => BudgetPeran::isAdmin($r->user()) ? $head : null, 'pic' => $r->input('pic')]))
            ->with('ok', $aktif
                ? 'Pembatasan PIC dihidupkan. PIC sekarang hanya melihat bagian yang ditugaskan.' . ($kosong ? ' ' . $kosong . ' PIC belum punya penugasan dan akan melihat halaman kosong sampai ditugaskan.' : '')
                : 'Pembatasan PIC dimatikan. PIC kembali melihat semua data departemen.');
    }

    /** Simpan penugasan seorang PIC: mengganti seluruh penugasannya di Dept Head ini. */
    public function simpan(Request $r)
    {
        abort_unless(BudgetPeran::fiturPicAktif(), 409, 'Jalankan database/sql/2026_11_26_budget_pic_tugas.sql dulu.');
        $head = $this->tentukanHead($r);
        abort_unless($head, 404);

        $r->validate([
            'pic' => ['required', 'integer'],
            'kategori' => ['nullable', 'array'], 'kategori.*' => ['integer'],
            'sub' => ['nullable', 'array'], 'sub.*' => ['integer'],
            'item' => ['nullable', 'array'], 'item.*' => ['integer'],
        ], [], ['pic' => 'PIC']);

        $picId = (int) $r->input('pic');
        $bawahan = BudgetPicHead::where('dept_head_user_id', $head)->where('pic_user_id', $picId)->where('aktif', true)->exists();
        if (!$bawahan) {
            return back()->withErrors(['tugas' => 'PIC yang dipilih bukan bawahan aktif Dept Head ini.']);
        }

        // Hanya bagian milik Dept Head ini yang boleh ditugaskan
        $kat = BudgetKategori::whereIn('id', (array) $r->input('kategori', []))
            ->where(fn ($q) => $q->where('dept_head_user_id', $head)->orWhereHas('subs.items', fn ($i) => $i->where('dept_head_user_id', $head)))
            ->pluck('id')->all();
        $sub = BudgetSub::whereIn('id', (array) $r->input('sub', []))
            ->where(fn ($q) => $q->whereHas('kategori', fn ($k) => $k->where('dept_head_user_id', $head))->orWhereHas('items', fn ($i) => $i->where('dept_head_user_id', $head)))
            ->pluck('id')->all();
        $item = BudgetItem::whereIn('id', (array) $r->input('item', []))->where('dept_head_user_id', $head)->pluck('id')->all();

        $oleh = (int) $r->user()->id;
        $sebelum = BudgetPicTugas::where('dept_head_user_id', $head)->where('pic_user_id', $picId)->count();

        DB::transaction(function () use ($head, $picId, $kat, $sub, $item, $oleh) {
            BudgetPicTugas::where('dept_head_user_id', $head)->where('pic_user_id', $picId)->delete();
            $dasar = ['dept_head_user_id' => $head, 'pic_user_id' => $picId, 'dibuat_oleh' => $oleh];
            foreach ($kat as $id) {
                BudgetPicTugas::create($dasar + ['kategori_id' => $id]);
            }
            foreach ($sub as $id) {
                BudgetPicTugas::create($dasar + ['sub_id' => $id]);
            }
            foreach ($item as $id) {
                BudgetPicTugas::create($dasar + ['item_id' => $id]);
            }
            // Penunjukan PIC lama di item ikut mengikuti centang: item yang tidak dicentang tidak lagi menunjuk PIC ini
            BudgetItem::where('dept_head_user_id', $head)->where('pic_user_id', $picId)->whereNotIn('id', $item)->update(['pic_user_id' => null]);
        });
        BudgetPeran::lupakan($picId);
        BudgetCatat::tulis('ubah', 'pic', $picId, 'Tugas PIC user #' . $picId . ' di Dept user #' . $head, ['baris' => $sebelum], ['kategori' => count($kat), 'sub' => count($sub), 'item' => count($item)]);

        return redirect()->route('budgeting.tugas', array_filter(['dept' => BudgetPeran::isAdmin($r->user()) ? $head : null, 'pic' => $picId]))
            ->with('ok', 'Tugas PIC disimpan: ' . count($kat) . ' kategori, ' . count($sub) . ' subkategori, ' . count($item) . ' item.');
    }
}
