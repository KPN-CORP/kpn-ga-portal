<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\Permintaan;
use App\Models\StockCtl\Stok;
use App\Models\StockCtl\Transaksi;
use App\Notifications\PermintaanDisetujui;
use App\Notifications\PermintaanDitolak;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalAdminController extends Controller
{
    public function index()
    {
        $access = session('stock_ctl_access');
        $query = Permintaan::with('pemohon', 'barang', 'areaKerja', 'approverL1')
            ->where('status', Permintaan::STATUS_PENDING_ADMIN);

        if (!$access['is_super']) {
            $query->whereExists(function ($q) use ($access) {
                $q->select(DB::raw(1))
                  ->from('stock_ctl_user_profil')
                  ->whereColumn('stock_ctl_user_profil.id_user', 'stock_ctl_permintaan.id_user_pemohon')
                  ->where('stock_ctl_user_profil.id_bisnis_unit', $access['id_bisnis_unit']);
            });
        }

        $permintaan = $query->orderBy('approved_l1_at')->get();
        $pendingCount = $permintaan->count();

        return view('stock-ctl.approval.admin.index', compact('permintaan', 'pendingCount'));
    }

    public function approve(Request $request, $id)
    {
        Log::info('ApprovalAdmin approve dipanggil', ['id' => $id, 'user' => Auth::id()]);

        // Validasi note (opsional)
        $request->validate([
            'catatan' => 'nullable|string|max:500'
        ]);

        DB::beginTransaction();
        try {
            $permintaan = Permintaan::with('pemohon', 'areaKerja')->findOrFail($id);
            $this->authorizeAdmin($permintaan);

            // Cek stok
            $stok = Stok::where('id_barang', $permintaan->id_barang)
                ->where('id_area_kerja', $permintaan->id_area_kerja)
                ->first();

            if (!$stok || $stok->jumlah < $permintaan->jumlah) {
                return back()->withErrors('Stok tidak mencukupi.');
            }

            // Kurangi stok
            $stok->decrement('jumlah', $permintaan->jumlah);

            // Catat transaksi
            Transaksi::create([
                'jenis'          => 'keluar',
                'id_barang'      => $permintaan->id_barang,
                'jumlah'         => $permintaan->jumlah,
                'id_area_asal'   => $permintaan->id_area_kerja,
                'keterangan'     => 'Dari permintaan #' . $permintaan->id_permintaan . '. Catatan admin: ' . ($request->catatan ?? '-'),
                'id_user'        => Auth::id(),
                'no_ref'         => 'PR-' . $permintaan->id_permintaan,
            ]);

            // Update permintaan
            $updateData = [
                'status'            => Permintaan::STATUS_APPROVED,
                'approved_admin_by' => Auth::id(),
                'approved_admin_at' => now(),
            ];
            if ($request->filled('catatan')) {
                $updateData['catatan_admin'] = $request->catatan;
            }
            $permintaan->update($updateData);

            // Kirim notifikasi ke pemohon (sertakan catatan jika ada)
            $permintaan->pemohon->notify(new PermintaanDisetujui($permintaan, $request->catatan));

            DB::commit();
            return redirect()->route('stock-ctl.approval.admin.index')->with('success', 'Permintaan disetujui.' . ($request->catatan ? ' Catatan telah dikirim.' : ''));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ApprovalAdmin approve gagal', ['error' => $e->getMessage()]);
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'alasan' => 'required|string'
        ]);

        try {
            $permintaan = Permintaan::with('pemohon')->findOrFail($id);
            $this->authorizeAdmin($permintaan);

            $permintaan->update([
                'status'           => Permintaan::STATUS_REJECTED,
                'rejected_by'      => Auth::id(),
                'rejected_at'      => now(),
                'alasan_tolak'     => $request->alasan,
            ]);

            $permintaan->pemohon->notify(new PermintaanDitolak($permintaan));

            return redirect()->route('stock-ctl.approval.admin.index')->with('success', 'Permintaan ditolak.');
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