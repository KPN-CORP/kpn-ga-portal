<?php

namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\Permintaan;
use App\Models\StockCtl\Stok;
use App\Models\StockCtl\Transaksi;
use App\Models\StockCtl\Barang;
use App\Models\StockCtl\Substitusi;
use App\Models\StockCtl\L1OverrideAdmin;
use App\Models\User;
use App\Notifications\PermintaanMenungguAdmin;
use App\Models\StockCtl\AreaKerja;
use App\Models\BisnisUnit;
use App\Notifications\PermintaanDisetujui;
use App\Notifications\PermintaanDitolak;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalAdminController extends Controller
{
    /**
     * Daftar permintaan dengan tab status (sama seperti stock-ctl/permintaan).
     * Default tab = Pending Admin (antrean kerja admin).
     * Cakupan data: superadmin = semua, admin = unit bisnisnya.
     * Tombol Setujui/Tolak hanya muncul di tab Pending Admin (diatur di view).
     */
    public function index(Request $request)
    {
        $access = session('stock_ctl_access');

        $statuses = [
            Permintaan::STATUS_PENDING_L1    => 'Pending L1',
            Permintaan::STATUS_PENDING_ADMIN => 'Pending Admin',
            Permintaan::STATUS_APPROVED      => 'Disetujui',
            Permintaan::STATUS_REJECTED      => 'Ditolak',
            Permintaan::STATUS_CANCELLED     => 'Dibatalkan',
        ];

        $status = $request->get('status', Permintaan::STATUS_PENDING_ADMIN);
        if (!isset($statuses[$status])) {
            $status = Permintaan::STATUS_PENDING_ADMIN;
        }

        // Unit bisnis efektif: admin dikunci ke unitnya, hanya superadmin yang boleh memilih.
        $unitAktif = $access['is_super']
            ? ($request->filled('id_bisnis_unit') ? $request->id_bisnis_unit : null)
            : $access['id_bisnis_unit'];

        // Default tanggal: jika admin tidak mengisi Dari/Sampai, riwayat (disetujui/ditolak/dibatalkan)
        // dibatasi ke BULAN BERJALAN. Permintaan aktif (Pending L1 & Pending Admin) tetap tampil semua.
        $defaultBulan = !$request->filled('dari') && !$request->filled('sampai');
        $now          = \Carbon\Carbon::now('Asia/Jakarta');
        $awalBulan    = $now->copy()->startOfMonth()->toDateString();
        $akhirBulan   = $now->copy()->endOfMonth()->toDateString();
        $labelBulan   = $now->copy()->locale('id')->translatedFormat('F Y');

        // Filter bersama (scope unit + filter pencarian), dipakai untuk daftar dan hitungan tab
        $terapkanFilter = function ($q) use ($request, $unitAktif, $defaultBulan, $awalBulan, $akhirBulan) {
            if ($unitAktif) {
                $q->whereExists(function ($sub) use ($unitAktif) {
                    $sub->select(DB::raw(1))
                        ->from('stock_ctl_user_profil')
                        ->whereColumn('stock_ctl_user_profil.id_user', 'stock_ctl_permintaan.id_user_pemohon')
                        ->where('stock_ctl_user_profil.id_bisnis_unit', $unitAktif);
                });
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $q->whereHas('barang', function ($b) use ($search) {
                    $b->where(function ($w) use ($search) {
                        $w->where('nama_barang', 'like', "%{$search}%")
                          ->orWhere('kode_barang', 'like', "%{$search}%");
                    });
                });
            }
            if ($request->filled('pemohon')) {
                $nama = $request->pemohon;
                $q->whereHas('pemohon', function ($p) use ($nama) {
                    $p->where('name', 'like', "%{$nama}%");
                });
            }
            if ($request->filled('id_area_kerja')) {
                $q->where('stock_ctl_permintaan.id_area_kerja', $request->id_area_kerja);
            }
            if ($request->filled('dari')) {
                $q->whereDate('stock_ctl_permintaan.tanggal_permintaan', '>=', $request->dari);
            }
            if ($request->filled('sampai')) {
                $q->whereDate('stock_ctl_permintaan.tanggal_permintaan', '<=', $request->sampai);
            }
            if ($defaultBulan) {
                $q->where(function ($w) use ($awalBulan, $akhirBulan) {
                    $w->whereIn('stock_ctl_permintaan.status', [
                            Permintaan::STATUS_PENDING_L1,
                            Permintaan::STATUS_PENDING_ADMIN,
                        ])
                      ->orWhere(function ($r) use ($awalBulan, $akhirBulan) {
                          $r->whereDate('stock_ctl_permintaan.tanggal_permintaan', '>=', $awalBulan)
                            ->whereDate('stock_ctl_permintaan.tanggal_permintaan', '<=', $akhirBulan);
                      });
                });
            }
        };

        // Hitungan per tab (mengikuti filter yang sedang aktif, kecuali status)
        $hitung = Permintaan::query();
        $terapkanFilter($hitung);
        $counts = $hitung->select('stock_ctl_permintaan.status', DB::raw('COUNT(*) as total'))
            ->groupBy('stock_ctl_permintaan.status')
            ->pluck('total', 'status');

        // Daftar untuk tab aktif
        $query = Permintaan::with([
                'pemohon.profil.approver',
                'barang',
                'areaKerja.bisnisUnit',
                'approverL1',
                'approverAdmin',
                'rejectedBy',
                'substitusi.admin',
            ])
            ->where('stock_ctl_permintaan.status', $status);
        $terapkanFilter($query);

        if ($status === Permintaan::STATUS_PENDING_ADMIN) {
            $query->orderBy('stock_ctl_permintaan.approved_l1_at');      // antrean: yang paling lama dulu
        } else {
            $query->orderByDesc('stock_ctl_permintaan.tanggal_permintaan');
        }

        $permintaan = $query->paginate(15)->withQueryString();

        // Data dropdown filter
        $bisnisUnits = $access['is_super']
            ? BisnisUnit::orderBy('nama_bisnis_unit')->get()
            : collect();

        $areaQuery = AreaKerja::with('bisnisUnit')->orderBy('nama_area');
        if ($unitAktif) {
            $areaQuery->where('id_bisnis_unit', $unitAktif);
        }
        $areas = $areaQuery->get();

        $pendingCount = (int) ($counts[Permintaan::STATUS_PENDING_ADMIN] ?? 0);

        // Admin yang terdaftar di stock_ctl_l1_override_admins boleh approve/tolak L1 dari tab Pending L1.
        $canApproveL1 = $this->bolehApproveL1();

        return view('stock-ctl.approval.admin.index', compact(
            'permintaan', 'pendingCount', 'status', 'statuses', 'counts', 'bisnisUnits', 'areas',
            'defaultBulan', 'labelBulan', 'canApproveL1'
        ));
    }

    /**
     * Cek stok untuk permintaan (AJAX)
     */
    public function cekStok($id)
    {
        $permintaan = Permintaan::findOrFail($id);
        $stok = Stok::where('id_barang', $permintaan->id_barang)
                    ->where('id_area_kerja', $permintaan->id_area_kerja)
                    ->first();
        $tersedia = $stok ? $stok->jumlah : 0;
        return response()->json(['tersedia' => $tersedia]);
    }

    public function approve(Request $request, $id)
    {
        Log::info('ApprovalAdmin approve dipanggil', [
            'id' => $id,
            'user' => Auth::id(),
            'request_data' => $request->all()
        ]);

        $request->validate([
            'jumlah_setuju' => 'required|numeric|min:0.01',
            'catatan' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $permintaan = Permintaan::with('pemohon', 'barang', 'areaKerja')->findOrFail($id);
            $this->authorizeAdmin($permintaan);

            $jumlahBaru = $request->jumlah_setuju;
            $satuan = $permintaan->barang->satuan ?? '';

            // Cek stok dengan jumlah baru
            $stok = Stok::where('id_barang', $permintaan->id_barang)
                ->where('id_area_kerja', $permintaan->id_area_kerja)
                ->first();

            if (!$stok || $stok->jumlah < $jumlahBaru) {
                $tersedia = $stok ? $stok->jumlah : 0;
                throw new \Exception("Tidak dapat menyetujui karena stok barang tidak mencukupi. Tersedia: " . number_format($tersedia) . " $satuan, diminta: " . number_format($jumlahBaru) . " $satuan");
            }

            // Kurangi stok
            $stok->decrement('jumlah', $jumlahBaru);

            // Catat transaksi
            Transaksi::create([
                'jenis' => 'keluar',
                'id_barang' => $permintaan->id_barang,
                'jumlah' => $jumlahBaru,
                'id_area_asal' => $permintaan->id_area_kerja,
                'keterangan' => 'Dari permintaan ' . $permintaan->no_tampil . '. Jumlah direvisi admin.' . ($request->catatan ? " Catatan: " . $request->catatan : ''),
                'id_user' => Auth::id(),
                'no_ref' => $permintaan->no_tampil,
            ]);

            // Update permintaan
            $updateData = [
                'jumlah' => $jumlahBaru,
                'status' => Permintaan::STATUS_APPROVED,
                'approved_admin_by' => Auth::id(),
                'approved_admin_at' => now(),
            ];
            if ($request->filled('catatan')) {
                $updateData['catatan_admin'] = $request->catatan;
            }
            // Snapshot harga saat disetujui (dipakai dashboard agar nilai tidak ikut berubah bila harga barang diubah)
            if (Permintaan::adaKolomHarga()) {
                $updateData['harga_satuan'] = (float) ($permintaan->barang->harga ?? 0);
            }

            $permintaan->update($updateData);

            // Kirim notifikasi (opsional, sesuaikan dengan notifikasi Anda)
            if (method_exists($permintaan->pemohon, 'notify')) {
                $permintaan->pemohon->notify(new PermintaanDisetujui($permintaan, $request->catatan, $jumlahBaru));
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Permintaan disetujui.']);
            }

            return redirect($this->backUrl($request))
                ->with('success', "Permintaan {$permintaan->no_tampil} disetujui. Jumlah: " . number_format($jumlahBaru) . " $satuan" . ($request->catatan ? ". Catatan: {$request->catatan}" : ""));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ApprovalAdmin approve gagal', [
                'error' => $e->getMessage(),
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
    }


    /**
     * Cari barang pengganti (AJAX) - berdasarkan nama / kode / deskripsi (merek ada di nama barang).
     * Stok yang ditampilkan adalah stok di AREA KERJA permintaan yang sama.
     */
    public function cariPengganti(Request $request, $id)
    {
        $permintaan = Permintaan::with('barang')->findOrFail($id);
        $this->authorizeAdmin($permintaan);

        $q    = trim((string) $request->get('q', ''));
        $area = $permintaan->id_area_kerja;

        $query = Barang::query()
            ->leftJoin('stock_ctl_stok as s', function ($j) use ($area) {
                $j->on('s.id_barang', '=', 'stock_ctl_barang.id_barang')
                  ->where('s.id_area_kerja', '=', $area);
            })
            ->where('stock_ctl_barang.id_barang', '!=', $permintaan->id_barang)
            ->select(
                'stock_ctl_barang.id_barang',
                'stock_ctl_barang.kode_barang',
                'stock_ctl_barang.nama_barang',
                'stock_ctl_barang.satuan',
                DB::raw('COALESCE(s.jumlah, 0) as stok')
            );

        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $query->where(function ($w) use ($like) {
                $w->where('stock_ctl_barang.nama_barang', 'like', $like)
                  ->orWhere('stock_ctl_barang.kode_barang', 'like', $like)
                  ->orWhere('stock_ctl_barang.deskripsi', 'like', $like);
            });
        }

        $items = $query->orderByDesc('stok')->orderBy('stock_ctl_barang.nama_barang')->limit(20)->get();

        $stokAsli = Stok::where('id_barang', $permintaan->id_barang)
            ->where('id_area_kerja', $area)
            ->value('jumlah');

        return response()->json([
            'asli' => [
                'nama'   => $permintaan->barang->nama_barang ?? '-',
                'satuan' => $permintaan->barang->satuan ?? '',
                'jumlah' => (float) $permintaan->jumlah,
                'stok'   => (float) ($stokAsli ?? 0),
            ],
            'items' => $items,
        ]);
    }

    /**
     * Ganti barang + setujui dalam SATU transaksi database.
     * Hanya boleh jika stok barang asli = 0 (dicek di server, bukan hanya di tampilan).
     * - Permintaan asli (id_barang, jumlah) tidak diubah.
     * - Barang asli & pengganti disimpan di stock_ctl_substitusi.
     * - Stok barang pengganti dikurangi, transaksi keluar dicatat atas barang pengganti.
     * - Gagal di langkah mana pun => rollback (stok tidak berkurang).
     * - Tidak perlu persetujuan tambahan dari user.
     */
    public function substitusi(Request $request, $id)
    {
        $request->validate([
            'id_barang_pengganti' => 'required|integer|exists:stock_ctl_barang,id_barang',
            'jumlah_pengganti'    => 'required|numeric|min:0.01',
            'alasan'              => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            // Kunci baris permintaan agar tidak diproses ganda (dua admin / klik dua kali)
            $permintaan = Permintaan::with('pemohon', 'barang', 'areaKerja')
                ->lockForUpdate()
                ->findOrFail($id);
            $this->authorizeAdmin($permintaan);

            if (Substitusi::where('id_permintaan', $permintaan->id_permintaan)->exists()) {
                throw new \Exception('Permintaan ini sudah memiliki substitusi.');
            }

            if ((int) $request->id_barang_pengganti === (int) $permintaan->id_barang) {
                throw new \Exception('Barang pengganti tidak boleh sama dengan barang yang diminta.');
            }

            $jumlah = (float) $request->jumlah_pengganti;
            if ($jumlah > (float) $permintaan->jumlah) {
                throw new \Exception('Jumlah pengganti tidak boleh melebihi jumlah yang diminta ('
                    . (float) $permintaan->jumlah . ').');
            }

            // Ganti Barang hanya boleh jika stok barang asli HABIS (0) di area kerja permintaan
            $stokAsli = Stok::where('id_barang', $permintaan->id_barang)
                ->where('id_area_kerja', $permintaan->id_area_kerja)
                ->lockForUpdate()
                ->first();
            if ($stokAsli && (float) $stokAsli->jumlah > 0) {
                throw new \Exception('Stok barang asli masih tersedia (' . (float) $stokAsli->jumlah
                    . '). Ganti Barang hanya dapat dilakukan jika stok barang yang diminta habis.');
            }

            $pengganti = Barang::findOrFail($request->id_barang_pengganti);

            // Validasi + kunci stok barang pengganti di area kerja permintaan
            $stok = Stok::where('id_barang', $pengganti->id_barang)
                ->where('id_area_kerja', $permintaan->id_area_kerja)
                ->lockForUpdate()
                ->first();

            $tersedia = $stok ? (float) $stok->jumlah : 0;
            if ($tersedia < $jumlah) {
                throw new \Exception("Stok {$pengganti->nama_barang} tidak mencukupi. Tersedia: "
                    . (float) $tersedia . " {$pengganti->satuan}, dibutuhkan: " . (float) $jumlah . " {$pengganti->satuan}");
            }

            $stok->decrement('jumlah', $jumlah);

            $namaAsli = $permintaan->barang->nama_barang ?? '-';
            $noTampil = $permintaan->no_tampil;

            $trx = Transaksi::create([
                'jenis'       => 'keluar',
                'id_barang'   => $pengganti->id_barang,
                'jumlah'      => $jumlah,
                'id_area_asal'=> $permintaan->id_area_kerja,
                'keterangan'  => "Dari permintaan {$noTampil}. Substitusi: {$namaAsli} diganti {$pengganti->nama_barang}. Alasan: {$request->alasan}",
                'id_user'     => Auth::id(),
                'no_ref'      => $noTampil,
            ]);

            $sub = Substitusi::create([
                'id_permintaan'       => $permintaan->id_permintaan,
                'no_permintaan'       => $noTampil,
                'id_barang_asli'      => $permintaan->id_barang,
                'nama_asli'           => $namaAsli,
                'jumlah_diminta'      => $permintaan->jumlah,
                'id_barang_pengganti' => $pengganti->id_barang,
                'nama_pengganti'      => $pengganti->nama_barang,
                'jumlah_pengganti'    => $jumlah,
                'alasan'              => $request->alasan,
                'id_admin'            => Auth::id(),
                'id_transaksi'        => $trx->id_transaksi ?? null,
                'created_at'          => now(),
            ]);

            // id_barang & jumlah permintaan sengaja TIDAK diubah
            $dataSetuju = [
                'status'            => Permintaan::STATUS_APPROVED,
                'approved_admin_by' => Auth::id(),
                'approved_admin_at' => now(),
                'catatan_admin'     => "Barang diganti: {$namaAsli} -> {$pengganti->nama_barang}. {$request->alasan}",
            ];
            // Snapshot harga barang PENGGANTI (yang benar-benar dikeluarkan)
            if (Permintaan::adaKolomHarga()) {
                $dataSetuju['harga_satuan'] = (float) ($pengganti->harga ?? 0);
            }
            $permintaan->update($dataSetuju);

            // Audit log
            Log::info('AUDIT substitusi barang', [
                'id_substitusi' => $sub->id_substitusi,
                'permintaan'    => $noTampil,
                'barang_asli'   => $namaAsli,
                'jumlah_diminta'=> (float) $permintaan->jumlah,
                'pengganti'     => $pengganti->nama_barang,
                'jumlah_pengganti' => $jumlah,
                'alasan'        => $request->alasan,
                'admin'         => Auth::user()->name ?? Auth::id(),
                'waktu'         => now()->toDateTimeString(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ApprovalAdmin substitusi gagal', ['error' => $e->getMessage(), 'id' => $id]);

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }

        // Notifikasi setelah commit; kegagalan notifikasi tidak membatalkan substitusi
        try {
            if (method_exists($permintaan->pemohon, 'notify')) {
                $permintaan->pemohon->notify(new PermintaanDisetujui(
                    $permintaan,
                    "Barang diganti menjadi {$pengganti->nama_barang}. Alasan: {$request->alasan}",
                    $jumlah
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Notifikasi substitusi gagal', ['error' => $e->getMessage()]);
        }

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Barang diganti dan permintaan disetujui.']);
        }
        return redirect($this->backUrl($request))
            ->with('success', "Permintaan {$noTampil} disetujui dengan barang pengganti {$pengganti->nama_barang}.");
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['alasan' => 'required|string']);

        try {
            $permintaan = Permintaan::with('pemohon')->findOrFail($id);
            $this->authorizeAdmin($permintaan);

            $permintaan->update([
                'status' => Permintaan::STATUS_REJECTED,
                'rejected_by' => Auth::id(),
                'rejected_at' => now(),
                'alasan_tolak' => $request->alasan,
            ]);

            $permintaan->pemohon->notify(new PermintaanDitolak($permintaan));

            return redirect($this->backUrl($request))
                ->with('success', 'Permintaan ditolak.');
        } catch (\Exception $e) {
            Log::error('ApprovalAdmin reject gagal', ['error' => $e->getMessage()]);
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Admin yang terdaftar di stock_ctl_l1_override_admins boleh bertindak atas nama atasan (L1).
     */
    private function bolehApproveL1(): bool
    {
        return L1OverrideAdmin::where('user_id', Auth::id())->exists();
    }

    /**
     * Ambil permintaan untuk aksi override L1: admin harus terdaftar, permintaan harus
     * berstatus pending_l1 dan berada di unit bisnis admin (superadmin lolos cek unit).
     */
    private function findForL1Override($id): Permintaan
    {
        $access = session('stock_ctl_access');

        if (!$this->bolehApproveL1()) {
            abort(403, 'Anda tidak terdaftar sebagai admin yang boleh approve L1.');
        }

        $permintaan = Permintaan::with('pemohon', 'barang', 'areaKerja')->findOrFail($id);

        if (empty($access['is_super'])) {
            $unitPemohon = DB::table('stock_ctl_user_profil')
                ->where('id_user', $permintaan->id_user_pemohon)
                ->value('id_bisnis_unit');
            if ($unitPemohon != ($access['id_bisnis_unit'] ?? null)) {
                abort(403, 'Unit bisnis tidak sesuai.');
            }
        }

        if ($permintaan->status != Permintaan::STATUS_PENDING_L1) {
            abort(400, 'Permintaan ini sudah tidak berstatus Pending L1.');
        }

        return $permintaan;
    }

    /**
     * Approve L1 atas nama atasan oleh admin terdaftar. Alur sama dengan approve L1 biasa:
     * pending_l1 -> pending_admin, lalu admin unit mendapat notifikasi.
     */
    public function approveL1(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $permintaan = $this->findForL1Override($id);

            $permintaan->update([
                'status'         => Permintaan::STATUS_PENDING_ADMIN,
                'approved_l1_by' => Auth::id(),
                'approved_l1_at' => now(),
                'l1_by_admin'    => 1,
            ]);

            Log::info('AUDIT approve L1 oleh admin atas nama atasan', [
                'permintaan' => $permintaan->no_tampil,
                'admin'      => Auth::user()->name ?? Auth::id(),
                'waktu'      => now()->toDateTimeString(),
            ]);

            DB::commit();
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ApprovalAdmin approveL1 gagal', ['error' => $e->getMessage(), 'id' => $id]);
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }

        // Notifikasi ke admin unit setelah commit; kegagalan notifikasi tidak membatalkan approve
        try {
            foreach ($this->getAdminUsersByUnit($permintaan->id_user_pemohon) as $admin) {
                $admin->notify(new PermintaanMenungguAdmin($permintaan));
            }
        } catch (\Throwable $e) {
            Log::warning('Notifikasi approve L1 oleh admin gagal', ['error' => $e->getMessage()]);
        }

        return redirect($this->backUrl($request))
            ->with('success', "Permintaan {$permintaan->no_tampil} disetujui (L1) atas nama atasan dan diteruskan ke Pending Admin.");
    }

    /**
     * Tolak L1 atas nama atasan oleh admin terdaftar.
     */
    public function rejectL1(Request $request, $id)
    {
        $request->validate(['alasan' => 'required|string|max:500']);

        try {
            $permintaan = $this->findForL1Override($id);

            $permintaan->update([
                'status'       => Permintaan::STATUS_REJECTED,
                'rejected_by'  => Auth::id(),
                'rejected_at'  => now(),
                'alasan_tolak' => 'Ditolak L1 oleh admin atas nama atasan: ' . $request->alasan,
                'l1_by_admin'  => 1,
            ]);

            Log::info('AUDIT tolak L1 oleh admin atas nama atasan', [
                'permintaan' => $permintaan->no_tampil,
                'admin'      => Auth::user()->name ?? Auth::id(),
                'alasan'     => $request->alasan,
                'waktu'      => now()->toDateTimeString(),
            ]);

            try {
                $permintaan->pemohon->notify(new PermintaanDitolak($permintaan));
            } catch (\Throwable $e) {
                Log::warning('Notifikasi tolak L1 oleh admin gagal', ['error' => $e->getMessage()]);
            }

            return redirect($this->backUrl($request))
                ->with('success', "Permintaan {$permintaan->no_tampil} ditolak (L1) atas nama atasan.");
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('ApprovalAdmin rejectL1 gagal', ['error' => $e->getMessage(), 'id' => $id]);
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Admin unit bisnis pemohon (sama dengan penerima notifikasi pada approve L1 biasa).
     */
    private function getAdminUsersByUnit($idPemohon)
    {
        $unitPemohon = DB::table('stock_ctl_user_profil')
            ->where('id_user', $idPemohon)
            ->value('id_bisnis_unit');
        if (!$unitPemohon) {
            return collect();
        }

        $adminUserIds = DB::table('stock_ctl_user_profil')
            ->join('users', 'users.id', '=', 'stock_ctl_user_profil.id_user')
            ->join('tb_access_menu', function ($join) {
                $join->on(DB::raw('tb_access_menu.username COLLATE utf8mb4_unicode_ci'), '=', DB::raw('users.username COLLATE utf8mb4_unicode_ci'));
            })
            ->where('tb_access_menu.stock_ctl_admin', 1)
            ->where('stock_ctl_user_profil.id_bisnis_unit', $unitPemohon)
            ->pluck('users.id');

        return User::whereIn('id', $adminUserIds)->get();
    }

    /**
     * URL kembali ke daftar approval dengan tab status + filter yang sedang aktif
     * (dikirim dari halaman lewat input hidden back_query). Hanya parameter yang
     * dikenal yang dipakai; default tab = pending_admin.
     */
    private function backUrl(Request $request)
    {
        parse_str((string) $request->input('back_query', ''), $q);
        $q = array_intersect_key($q, array_flip([
            'status', 'search', 'pemohon', 'id_area_kerja', 'id_bisnis_unit', 'dari', 'sampai', 'page',
        ]));
        $q['status'] = $q['status'] ?? 'pending_admin';

        return route('stock-ctl.approval.admin.index', $q);
    }

    private function authorizeAdmin($permintaan)
    {
        $access = session('stock_ctl_access');

        if (!$access['is_super'] && !$access['is_admin']) {
            abort(403, 'Anda bukan admin.');
        }

        $unitPemohon = DB::table('stock_ctl_user_profil')
            ->where('id_user', $permintaan->id_user_pemohon)
            ->value('id_bisnis_unit');

        if (!$access['is_super'] && $unitPemohon != $access['id_bisnis_unit']) {
            abort(403, 'Unit bisnis tidak sesuai.');
        }

        if ($permintaan->status != Permintaan::STATUS_PENDING_ADMIN) {
            abort(400, 'Permintaan sudah diproses.');
        }
    }
}