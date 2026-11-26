<?php

namespace App\Http\Controllers\IDCard;

use App\Models\IDCard\IdCardKartuPinjam;
use App\Services\IDCardStokService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Daftar kartu pinjam (magang / visitor). Tidak terkait stok: hanya status Digunakan / Ada.
 */
class KartuPinjamController extends IDCardBaseController
{
    protected IDCardStokService $stok;

    public function __construct(IDCardStokService $stok)
    {
        $this->stok = $stok;
    }

    public function index(Request $request)
    {
        if (!$this->canAksesKartuPinjam()) {
            return redirect()->route('no-access')->with('error', 'Anda tidak memiliki akses ke halaman kartu pinjam.');
        }

        $q = IdCardKartuPinjam::orderBy('nomor_kartu');
        if ($request->filled('search')) {
            $q->where('nomor_kartu', 'like', '%' . trim($request->search) . '%');
        }
        if ($request->filled('jenis') && $request->jenis !== 'all') {
            $q->where('jenis', $request->jenis);
        }

        $rows = $this->stok->statusKartuPinjam($q->get());

        $ringkas = [
            'total'     => $rows->count(),
            'digunakan' => $rows->where('status', 'digunakan')->count(),
            'ada'       => $rows->where('status', 'ada')->count(),
            'nonaktif'  => $rows->where('status', 'nonaktif')->count(),
        ];

        if ($request->filled('status') && $request->status !== 'all') {
            $rows = $rows->where('status', $request->status)->values();
        }

        $perPage = 15;
        $page    = LengthAwarePaginator::resolveCurrentPage();
        $data    = new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values(),
            $rows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $isSuper = $this->isSuperAdmin();
        $adminBU = $this->getAdminBUAccess();

        return view('idcard.kartu-pinjam', compact('data', 'ringkas', 'isSuper', 'adminBU'));
    }

    public function store(Request $req)
    {
        if (!$this->canAksesKartuPinjam()) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $req->merge(['nomor_kartu' => trim((string) $req->nomor_kartu)]);

        $v = Validator::make($req->all(), [
            'nomor_kartu' => 'required|string|max:50|unique:idcard_kartu_pinjam,nomor_kartu',
            'jenis'       => 'required|in:magang,visitor,umum',
            'keterangan'  => 'nullable|string|max:255',
        ], ['nomor_kartu.unique' => 'Nomor kartu ini sudah terdaftar.']);
        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        IdCardKartuPinjam::create([
            'nomor_kartu' => $req->nomor_kartu,
            'jenis'       => $req->jenis,
            'keterangan'  => $req->keterangan,
            'is_active'   => 1,
            'created_by'  => Auth::id(),
        ]);

        return back()->with('success', 'Kartu pinjam ditambahkan.');
    }

    public function update(Request $req, $id)
    {
        if (!$this->canAksesKartuPinjam()) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $kartu = IdCardKartuPinjam::findOrFail($id);

        $v = Validator::make($req->all(), [
            'jenis'      => 'required|in:magang,visitor,umum',
            'keterangan' => 'nullable|string|max:255',
        ]);
        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }

        $beredar = $req->boolean('is_active');

        // Kartu yang sedang dipakai tidak boleh ditarik dari peredaran sebelum dikembalikan
        if (!$beredar && $kartu->is_active) {
            $status = $this->stok->statusKartuPinjam(collect([$kartu]))->first();
            if ($status->status === 'digunakan') {
                return back()->with('error', 'Kartu ' . $kartu->nomor_kartu . ' sedang digunakan oleh ' . optional($status->pemakai)->nama . '. Proses pengembalian kartu dulu.');
            }
        }

        $kartu->jenis      = $req->jenis;
        $kartu->keterangan = $req->keterangan;
        $kartu->is_active  = $beredar;
        $kartu->save();

        return back()->with('success', 'Kartu pinjam diperbarui.');
    }

    public function destroy($id)
    {
        if (!$this->canAksesKartuPinjam()) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $kartu  = IdCardKartuPinjam::findOrFail($id);
        $status = $this->stok->statusKartuPinjam(collect([$kartu]))->first();

        // Kartu yang sedang dipakai tidak boleh dihapus sebelum dikembalikan
        if ($status->status === 'digunakan') {
            return back()->with('error', 'Kartu ' . $kartu->nomor_kartu . ' sedang digunakan oleh ' . optional($status->pemakai)->nama . '. Proses pengembalian kartu dulu.');
        }

        $nomor = $kartu->nomor_kartu;
        $kartu->delete();
        \Log::info('IDCard kartu pinjam dihapus: ' . $nomor . ' (id ' . $id . ') oleh user ' . Auth::id());

        return back()->with('success', 'Kartu ' . $nomor . ' dihapus dari daftar. Riwayat pemakaian di request tidak ikut terhapus.');
    }
}
