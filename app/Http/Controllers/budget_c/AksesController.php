<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Http\Requests\budget_r\SimpanAksesRequest;
use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetSub;
use App\Models\budget_m\BudgetPicHead;
use App\Support\budget_s\BudgetCatat;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AksesController extends Controller
{
    /** Admin: kelola Admin dan Dept (lihat saja PIC). Dept: kelola PIC bawahannya ("PIC Saya"). */
    public function index(Request $r)
    {
        $user = $r->user();
        $peran = BudgetPeran::peran($user);
        $urutan = ['admin', 'dept_head', 'pic'];

        if ($peran === BudgetPeran::ADMIN) {
            $akses = BudgetAkses::with('user')->get()
                ->sortBy(fn ($a) => [array_search($a->peran, $urutan), $a->user?->name])->values();
            $picHead = BudgetPicHead::with(['pic', 'deptHead'])->where('aktif', true)->get();
            $kepala = $akses->where('peran', BudgetPeran::DEPT_HEAD)->where('aktif', true)->values();
            $jumlahItem = BudgetItem::selectRaw('dept_head_user_id, count(*) as n')->groupBy('dept_head_user_id')->pluck('n', 'dept_head_user_id');
        } else {
            $jumlahItem = collect();
            $akses = collect();
            $kepala = collect();
            $picHead = BudgetPicHead::with('pic')->where('dept_head_user_id', $user->id)->where('aktif', true)->get();
        }

        return view('budget_p.akses.index', compact('peran', 'akses', 'picHead', 'kepala', 'jumlahItem'));
    }

    /** Admin: tambah user ke modul budget / ubah peran / aktif-nonaktif. */
    public function simpan(SimpanAksesRequest $r)
    {
        $uid = (int) $r->input('user_id');
        $peranBaru = $r->input('peran');
        $aktif = $r->boolean('aktif');
        $lama = BudgetAkses::where('user_id', $uid)->first();

        if ($lama && $lama->peran === BudgetPeran::PIC) {
            return back()->withFragment('pesan')->withErrors(['akses' => 'PIC ditambah dan dilepas oleh Dept-nya, bukan oleh Admin.']);
        }

        if ($lama && $lama->peran === BudgetPeran::ADMIN && $lama->aktif && ($peranBaru !== BudgetPeran::ADMIN || !$aktif)) {
            $adaLain = BudgetAkses::where('peran', BudgetPeran::ADMIN)->where('aktif', true)->where('user_id', '<>', $uid)->exists();
            if (!$adaLain) {
                return back()->withFragment('pesan')->withErrors(['akses' => 'Harus ada minimal satu Admin aktif.']);
            }
        }
        if ($lama && $lama->peran === BudgetPeran::DEPT_HEAD && $peranBaru !== BudgetPeran::DEPT_HEAD) {
            $sisaItem = BudgetItem::where('dept_head_user_id', $uid)->where('aktif', true)->orderBy('nama')->get(['id', 'nama']);
            $sisa = $sisaItem->count();
            if ($sisa > 0) {
                return back()->withFragment('pesan')->withErrors(['akses' => 'Dept ini masih memiliki ' . $sisa . ' item budget aktif: ' . $sisaItem->take(8)->map(fn ($i) => $i->nama . ' (#' . $i->id . ')')->implode(', ') . '. Pindahkan ke Dept lain dulu di Rencana Budget (ubah Departemen pemilik).']);
            }
        }

        $namaDept = $peranBaru === BudgetPeran::DEPT_HEAD ? (trim((string) $r->input('nama_dept')) ?: null) : null;

        DB::transaction(function () use ($uid, $peranBaru, $aktif, $lama, $namaDept) {
            BudgetAkses::updateOrCreate(['user_id' => $uid], [
                'peran' => $peranBaru,
                'nama_dept' => $namaDept,
                'aktif' => $aktif,
            ]);

            if ($lama && $lama->peran === BudgetPeran::PIC && $peranBaru !== BudgetPeran::PIC) {
                BudgetPicHead::where('pic_user_id', $uid)->delete();
                BudgetItem::where('pic_user_id', $uid)->update(['pic_user_id' => null]);
            }
            if ($lama && $lama->peran === BudgetPeran::DEPT_HEAD && $peranBaru !== BudgetPeran::DEPT_HEAD) {
                BudgetPicHead::where('dept_head_user_id', $uid)->delete();
            }
        });
        BudgetPeran::lupakan($uid);

        $baru = BudgetAkses::where('user_id', $uid)->first();
        BudgetCatat::tulis($lama ? 'ubah' : 'tambah', 'akses', $baru?->id, 'user #' . $uid . ' peran ' . $peranBaru, $lama?->getAttributes(), $baru?->getAttributes());

        return back()->withFragment('pesan')->with('ok', 'Akses disimpan.');
    }

    /** Dept: jadikan user sebagai PIC di bawahnya. Satu PIC boleh punya banyak Dept. */
    public function tambahPic(Request $r)
    {
        $user = $r->user();
        abort_unless(BudgetPeran::isDeptHead($user), 403);

        $r->validate(['pic_user_id' => ['required', 'integer', 'exists:users,id']], [], ['pic_user_id' => 'user PIC']);

        $head = (int) $user->id;
        $picId = (int) $r->input('pic_user_id');

        if ($picId === $head) {
            return back()->withFragment('pesan')->withErrors(['pic' => 'Anda tidak bisa menjadi PIC untuk diri sendiri.']);
        }

        $akses = BudgetAkses::where('user_id', $picId)->first();
        if ($akses && $akses->peran !== BudgetPeran::PIC) {
            return back()->withFragment('pesan')->withErrors(['pic' => 'User ini sudah berperan sebagai ' . BudgetPeran::label($akses->peran) . '. Satu user hanya boleh satu peran.']);
        }
        if ($akses && !$akses->aktif) {
            return back()->withFragment('pesan')->withErrors(['pic' => 'Akses PIC ini dinonaktifkan oleh Admin. Hubungi Admin untuk mengaktifkan kembali.']);
        }

        DB::transaction(function () use ($picId, $head, $akses) {
            BudgetAkses::updateOrCreate(['user_id' => $picId], ['peran' => BudgetPeran::PIC, 'aktif' => $akses ? $akses->aktif : true]);
            BudgetPicHead::updateOrCreate(['pic_user_id' => $picId, 'dept_head_user_id' => $head], ['aktif' => true]);
        });
        BudgetPeran::lupakan($picId);
        BudgetCatat::tulis('tambah', 'pic', $picId, 'PIC user #' . $picId . ' ke Dept user #' . $head, null, ['pic_user_id' => $picId, 'dept_head_user_id' => $head]);

        return back()->withFragment('pesan')->with('ok', 'PIC ditambahkan. PIC memiliki akses yang sama dengan Anda untuk data departemen ini.');
    }

    /**
     * Dept: lepas PIC miliknya. Item tetap milik Dept; PIC dikosongkan dari item departemen ini.
     * PIC yang tidak punya Dept lain langsung kehilangan akses (baris hak aksesnya dihapus).
     */
    public function lepasPic(Request $r, BudgetPicHead $picHead)
    {
        $user = $r->user();
        abort_unless(BudgetPeran::isDeptHead($user) && (int) $picHead->dept_head_user_id === (int) $user->id, 403);

        $picId = (int) $picHead->pic_user_id;
        $headId = (int) $picHead->dept_head_user_id;
        $akesDihapus = false;
        $sebelumPh = $picHead->getAttributes();

        DB::transaction(function () use ($picHead, $picId, $headId, &$akesDihapus) {
            $picHead->delete();
            BudgetItem::where('dept_head_user_id', $headId)->where('pic_user_id', $picId)->update(['pic_user_id' => null]);

            if (!BudgetPicHead::where('pic_user_id', $picId)->exists()) {
                $akesDihapus = BudgetAkses::where('user_id', $picId)->where('peran', BudgetPeran::PIC)->delete() > 0;
            }
        });
        BudgetPeran::lupakan($picId);
        BudgetCatat::tulis('hapus', 'pic', $picId, 'PIC user #' . $picId . ' dilepas dari Dept user #' . $headId, $sebelumPh, ['akses_dihapus' => $akesDihapus]);

        return back()->withFragment('pesan')->with('ok', $akesDihapus
            ? 'PIC dilepas dan hak aksesnya dihapus.'
            : 'PIC dilepas dari departemen Anda.');
    }

    /** Admin: hapus hak akses user dari database (beserta relasi PIC/Dept-nya). */
    public function hapus(BudgetAkses $akses)
    {
        $uid = (int) $akses->user_id;

        if ($akses->peran === BudgetPeran::PIC) {
            return back()->withFragment('pesan')->withErrors(['akses' => 'PIC dilepas oleh Dept-nya, bukan oleh Admin.']);
        }
        if ($uid === (int) auth()->id()) {
            return back()->withFragment('pesan')->withErrors(['akses' => 'Anda tidak bisa menghapus hak akses Anda sendiri.']);
        }
        if ($akses->peran === BudgetPeran::ADMIN && $akses->aktif
            && !BudgetAkses::where('peran', BudgetPeran::ADMIN)->where('aktif', true)->where('user_id', '<>', $uid)->exists()) {
            return back()->withFragment('pesan')->withErrors(['akses' => 'Harus ada minimal satu Admin aktif.']);
        }
        if ($akses->peran === BudgetPeran::DEPT_HEAD) {
            $sisaItem = BudgetItem::where('dept_head_user_id', $uid)->where('aktif', true)->orderBy('nama')->get(['id', 'nama']);
            $sisa = $sisaItem->count();
            if ($sisa > 0) {
                return back()->withFragment('pesan')->withErrors(['akses' => 'Dept ini masih memiliki ' . $sisa . ' item budget aktif: ' . $sisaItem->take(8)->map(fn ($i) => $i->nama . ' (#' . $i->id . ')')->implode(', ') . '. Pindahkan ke Dept lain dulu di Rencana Budget.']);
            }
        }

        $sebelum = $akses->getAttributes();
        DB::transaction(function () use ($akses, $uid) {
            BudgetPicHead::where('pic_user_id', $uid)->orWhere('dept_head_user_id', $uid)->delete();
            BudgetItem::where('pic_user_id', $uid)->update(['pic_user_id' => null]);
            $akses->delete();
        });
        BudgetPeran::lupakan($uid);
        BudgetCatat::tulis('hapus', 'akses', $sebelum['id'] ?? null, 'user #' . $uid . ' peran ' . ($sebelum['peran'] ?? '-'), $sebelum, null);

        return back()->withFragment('pesan')->with('ok', 'Hak akses dihapus.');
    }

    /**
     * Admin: pindahkan seluruh data satu Dept ke akun Dept lain (mis. saat orangnya diganti):
     * item (aktif dan nonaktif), PIC, kategori beserta sub, dan nama departemen.
     */
    public function pindahDept(Request $r)
    {
        $r->validate([
            'dari' => ['required', 'integer', 'different:ke'],
            'ke' => ['required', 'integer'],
        ], [], ['dari' => 'Dept asal', 'ke' => 'Dept tujuan']);

        $dari = (int) $r->input('dari');
        $ke = (int) $r->input('ke');

        $asal = BudgetAkses::where('user_id', $dari)->where('peran', BudgetPeran::DEPT_HEAD)->first();
        $tujuan = BudgetAkses::where('user_id', $ke)->where('peran', BudgetPeran::DEPT_HEAD)->where('aktif', true)->first();
        if (!$asal || !$tujuan) {
            return back()->withFragment('pesan')->withErrors(['akses' => 'Dept asal harus berperan Dept, dan Dept tujuan harus Dept yang aktif.']);
        }

        $jumlah = 0;
        DB::transaction(function () use ($dari, $ke, $asal, $tujuan, &$jumlah) {
            // 1. PIC: hubungkan ke Dept tujuan, lepas dari Dept asal
            foreach (BudgetPicHead::where('dept_head_user_id', $dari)->get() as $ph) {
                BudgetPicHead::updateOrCreate(['pic_user_id' => $ph->pic_user_id, 'dept_head_user_id' => $ke], ['aktif' => $ph->aktif]);
                $ph->delete();
            }

            // 2. Kategori dan subkategori (gabung bila nama yang sama sudah ada di Dept tujuan)
            foreach (BudgetKategori::where('dept_head_user_id', $dari)->get() as $k) {
                $t = BudgetKategori::where('dept_head_user_id', $ke)->where('nama', $k->nama)->first();
                if (!$t) {
                    $k->update(['dept_head_user_id' => $ke]);
                    continue;
                }
                foreach ($k->subs as $s) {
                    $ts = BudgetSub::where('kategori_id', $t->id)->where('nama', $s->nama)->first();
                    if ($ts) {
                        BudgetItem::where('sub_id', $s->id)->update(['sub_id' => $ts->id]);
                        $s->delete();
                    } else {
                        $s->update(['kategori_id' => $t->id]);
                    }
                }
                $k->delete();
            }

            // 3. Item (aktif dan nonaktif)
            $jumlah = BudgetItem::where('dept_head_user_id', $dari)->update(['dept_head_user_id' => $ke]);

            // 4. Nama departemen ikut pindah bila Dept tujuan belum punya
            if ($asal->nama_dept && !$tujuan->nama_dept) {
                $tujuan->update(['nama_dept' => $asal->nama_dept]);
            }
        });

        BudgetPeran::lupakan($dari);
        BudgetPeran::lupakan($ke);
        foreach (BudgetPicHead::where('dept_head_user_id', $ke)->pluck('pic_user_id') as $picId) {
            BudgetPeran::lupakan($picId);
        }

        BudgetCatat::tulis('pindah', 'akses', $ke, 'Dept user #' . $dari . ' dipindah ke Dept user #' . $ke, ['dari' => $dari], ['ke' => $ke, 'item' => $jumlah]);

        return back()->withFragment('pesan')->with('ok', $jumlah . ' item beserta PIC dan kategorinya dipindahkan ke ' . BudgetPeran::namaDept($ke) . '. Akun lama sekarang bisa diubah atau dihapus.');
    }
}