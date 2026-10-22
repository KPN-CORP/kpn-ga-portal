<?php

namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\Permintaan;
use App\Models\StockCtl\Stok;
use App\Models\StockCtl\Transaksi;
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

        // Filter bersama (scope unit + filter pencarian), dipakai untuk daftar dan hitungan tab
        $terapkanFilter = function ($q) use ($request, $unitAktif) {
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

        return view('stock-ctl.approval.admin.index', compact(
            'permintaan', 'pendingCount', 'status', 'statuses', 'counts', 'bisnisUnits', 'areas'
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

            $permintaan->update($updateData);

            // Kirim notifikasi (opsional, sesuaikan dengan notifikasi Anda)
            if (method_exists($permintaan->pemohon, 'notify')) {
                $permintaan->pemohon->notify(new PermintaanDisetujui($permintaan, $request->catatan, $jumlahBaru));
            }

            DB::commit();

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Permintaan disetujui.']);
            }

            return redirect()->route('stock-ctl.approval.admin.index', ['status' => 'pending_admin'])
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

            return redirect()->route('stock-ctl.approval.admin.index', ['status' => 'pending_admin'])
                ->with('success', 'Permintaan ditolak.');
        } catch (\Exception $e) {
            Log::error('ApprovalAdmin reject gagal', ['error' => $e->getMessage()]);
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
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