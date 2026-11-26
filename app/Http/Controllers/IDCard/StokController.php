<?php

namespace App\Http\Controllers\IDCard;

use App\Models\IDCard\IdCardStokLog;
use App\Models\User;
use App\Services\IDCardStokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Stok Card Holder / Lanyard / Kartu, PER ADMIN.
 * - Admin BU: mengelola stok miliknya sendiri.
 * - Superadmin: mengelola stok sendiri, dan bisa memilih admin lain (melihat semua admin dalam satu matriks).
 * Magang & visitor TIDAK ada di sini; lihat KartuPinjamController.
 */
class StokController extends IDCardBaseController
{
    protected IDCardStokService $stok;

    public function __construct(IDCardStokService $stok)
    {
        $this->stok = $stok;
    }

    /** Pemilik stok yang sedang dikelola: diri sendiri, kecuali superadmin memilih admin lain (owner_id). */
    private function pemilikId(Request $request): int
    {
        if ($this->isSuperAdmin() && $request->filled('owner_id')) {
            $id = (int) $request->owner_id;
            if (User::whereKey($id)->exists()) {
                return $id;
            }
        }
        return (int) Auth::id();
    }

    private function wajibAdmin(): void
    {
        if (!$this->canProses()) {
            abort(403, 'Hanya admin yang dapat mengelola stok.');
        }
    }

    public function index(Request $request)
    {
        if (!$this->canProses()) {
            return redirect()->route('no-access')->with('error', 'Anda tidak memiliki akses ke halaman stok.');
        }

        $isSuper = $this->isSuperAdmin();
        $ownerId = $this->pemilikId($request);
        $owner   = User::findOrFail($ownerId);

        $ringkasan = $this->stok->ringkasan($ownerId);
        $items     = $this->stok->items();

        // Superadmin: daftar admin untuk dipilih + matriks stok semua admin
        $admins  = $isSuper ? $this->stok->daftarAdmin() : collect();
        $matriks = $isSuper ? $this->stok->matriksSemuaAdmin() : collect();

        $q = IdCardStokLog::with(['item', 'creator', 'request'])
            ->where('user_id', $ownerId)
            ->orderByDesc('id');

        if ($request->filled('item_id')) {
            $q->where('item_id', $request->item_id);
        }
        if ($request->filled('tipe') && $request->tipe !== 'all') {
            $q->where('tipe', $request->tipe);
        }
        if ($request->filled('periode_awal')) {
            $q->whereDate('created_at', '>=', $request->periode_awal);
        }
        if ($request->filled('periode_akhir')) {
            $q->whereDate('created_at', '<=', $request->periode_akhir);
        }

        $riwayat = $q->paginate(15)->withQueryString();

        return view('idcard.stok', compact('ringkasan', 'items', 'riwayat', 'isSuper', 'owner', 'admins', 'matriks'));
    }

    public function masuk(Request $req)
    {
        $this->wajibAdmin();

        $v = Validator::make($req->all(), [
            'item_id' => 'required|exists:idcard_item,id',
            'qty'     => 'required|integer|min:1|max:100000',
            'catatan' => 'nullable|string|max:200',
        ], ['qty.min' => 'Jumlah stok masuk minimal 1.']);
        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $this->stok->stokMasuk($this->pemilikId($req), (int) $req->item_id, (int) $req->qty, Auth::id(), $req->catatan ?: 'Pembelian / stok masuk');

        return back()->with('success', 'Stok masuk tercatat.');
    }

    public function opname(Request $req)
    {
        $this->wajibAdmin();

        $v = Validator::make($req->all(), [
            'item_id'    => 'required|exists:idcard_item,id',
            'stok_fisik' => 'required|integer|min:0|max:1000000',
            'catatan'    => 'nullable|string|max:150',
        ]);
        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $log = $this->stok->opname($this->pemilikId($req), (int) $req->item_id, (int) $req->stok_fisik, Auth::id(), $req->catatan);

        return back()->with('success', $log ? 'Stok disesuaikan ke hasil hitung fisik.' : 'Stok sudah sama dengan hasil hitung fisik, tidak ada perubahan.');
    }

    public function minimum(Request $req)
    {
        $this->wajibAdmin();

        $v = Validator::make($req->all(), [
            'item_id'  => 'required|exists:idcard_item,id',
            'min_stok' => 'required|integer|min:0|max:1000000',
        ]);
        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $this->stok->setMinimum($this->pemilikId($req), (int) $req->item_id, (int) $req->min_stok);

        return back()->with('success', 'Stok minimum diperbarui.');
    }
}
