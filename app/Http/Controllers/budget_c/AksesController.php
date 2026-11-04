<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Http\Requests\budget_r\SimpanAksesRequest;
use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetPicHead;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AksesController extends Controller
{
    /** Admin: kelola semua akses + PIC. Dept Head: hanya daftar PIC bawahannya ("PIC Saya"). */
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
        } else {
            $akses = collect();
            $kepala = collect();
            $picHead = BudgetPicHead::with('pic')->where('dept_head_user_id', $user->id)->where('aktif', true)->get();
        }

        return view('budget_p.akses.index', compact('peran', 'akses', 'picHead', 'kepala'));
    }

    /** Admin: tambah user ke modul budget / ubah peran / aktif-nonaktif. */
    public function simpan(SimpanAksesRequest $r)
    {
        $uid = (int) $r->input('user_id');
        $peranBaru = $r->input('peran');
        $aktif = $r->boolean('aktif');
        $lama = BudgetAkses::where('user_id', $uid)->first();

        if ($lama && $lama->peran === BudgetPeran::ADMIN && $lama->aktif && ($peranBaru !== BudgetPeran::ADMIN || !$aktif)) {
            $adaLain = BudgetAkses::where('peran', BudgetPeran::ADMIN)->where('aktif', true)->where('user_id', '<>', $uid)->exists();
            if (!$adaLain) {
                return back()->withErrors(['akses' => 'Harus ada minimal satu Admin aktif.']);
            }
        }
        if ($lama && $lama->peran === BudgetPeran::DEPT_HEAD && $peranBaru !== BudgetPeran::DEPT_HEAD
            && BudgetItem::where('dept_head_user_id', $uid)->exists()) {
            return back()->withErrors(['akses' => 'User ini masih memiliki item budget sebagai Dept Head. Pindahkan item ke Dept Head lain dulu (Admin dapat mengubah pemilik item di halaman Budget).']);
        }

        DB::transaction(function () use ($uid, $peranBaru, $aktif, $lama) {
            BudgetAkses::updateOrCreate(['user_id' => $uid], ['peran' => $peranBaru, 'aktif' => $aktif]);

            if ($lama && $lama->peran === BudgetPeran::PIC && $peranBaru !== BudgetPeran::PIC) {
                BudgetPicHead::where('pic_user_id', $uid)->update(['aktif' => false]);
                BudgetItem::where('pic_user_id', $uid)->update(['pic_user_id' => null]);
            }
            if ($lama && $lama->peran === BudgetPeran::DEPT_HEAD && $peranBaru !== BudgetPeran::DEPT_HEAD) {
                BudgetPicHead::where('dept_head_user_id', $uid)->update(['aktif' => false]);
            }
        });
        BudgetPeran::lupakan($uid);

        return back()->with('ok', 'Akses disimpan.');
    }

    /** Admin atau Dept Head: jadikan user sebagai PIC di bawah seorang Dept Head. Satu PIC boleh punya banyak Dept Head. */
    public function tambahPic(Request $r)
    {
        $user = $r->user();
        $admin = BudgetPeran::isAdmin($user);

        $r->validate([
            'pic_user_id' => ['required', 'integer', 'exists:users,id'],
            'dept_head_user_id' => [$admin ? 'required' : 'nullable', 'integer'],
        ], [], ['pic_user_id' => 'user PIC', 'dept_head_user_id' => 'Dept Head']);

        $head = $admin ? (int) $r->input('dept_head_user_id') : (int) $user->id;
        $picId = (int) $r->input('pic_user_id');

        $headAktif = BudgetAkses::where('user_id', $head)->where('peran', BudgetPeran::DEPT_HEAD)->where('aktif', true)->exists();
        if (!$headAktif) {
            return back()->withErrors(['pic' => 'Dept Head yang dipilih tidak punya akses Dept Head yang aktif.']);
        }
        if ($picId === $head) {
            return back()->withErrors(['pic' => 'Dept Head tidak bisa menjadi PIC untuk dirinya sendiri.']);
        }

        $akses = BudgetAkses::where('user_id', $picId)->first();
        if ($akses && $akses->peran !== BudgetPeran::PIC) {
            return back()->withErrors(['pic' => 'User ini sudah berperan sebagai ' . BudgetPeran::label($akses->peran) . '. Satu user hanya boleh satu peran.']);
        }
        if ($akses && !$akses->aktif && !$admin) {
            return back()->withErrors(['pic' => 'Akses PIC ini dinonaktifkan oleh Admin. Hubungi Admin untuk mengaktifkan kembali.']);
        }

        DB::transaction(function () use ($picId, $head, $admin, $akses) {
            BudgetAkses::updateOrCreate(
                ['user_id' => $picId],
                ['peran' => BudgetPeran::PIC, 'aktif' => ($akses && !$admin) ? $akses->aktif : true]
            );
            BudgetPicHead::updateOrCreate(
                ['pic_user_id' => $picId, 'dept_head_user_id' => $head],
                ['aktif' => true]
            );
        });
        BudgetPeran::lupakan($picId);

        return back()->with('ok', 'PIC ditambahkan.');
    }

    /**
     * Lepas PIC dari satu Dept Head. Item tetap milik Dept Head tersebut,
     * dan PIC dikosongkan hanya dari item-item milik Dept Head itu.
     */
    public function lepasPic(Request $r, BudgetPicHead $picHead)
    {
        $user = $r->user();
        $boleh = BudgetPeran::isAdmin($user)
            || (BudgetPeran::isDeptHead($user) && (int) $picHead->dept_head_user_id === (int) $user->id);
        abort_unless($boleh, 403);

        DB::transaction(function () use ($picHead) {
            $picHead->update(['aktif' => false]);
            BudgetItem::where('dept_head_user_id', $picHead->dept_head_user_id)
                ->where('pic_user_id', $picHead->pic_user_id)
                ->update(['pic_user_id' => null]);
        });

        return back()->with('ok', 'PIC dilepas. Item tetap milik Dept Head dan kolom PIC pada item-item tersebut dikosongkan.');
    }
}
