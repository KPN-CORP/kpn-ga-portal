<?php
namespace App\Http\Controllers\StockCtl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockCtl\Permintaan;
use App\Models\StockCtl\UserProfil;
use App\Models\StockCtl\AreaKerja;
use App\Models\User;
use App\Notifications\PermintaanMenungguAdmin;
use App\Notifications\PermintaanDitolak;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ApprovalL1Controller extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $permintaan = Permintaan::with('pemohon', 'barang', 'areaKerja')
            ->where('status', Permintaan::STATUS_PENDING_L1)
            ->whereExists(function ($q) use ($user) {
                $q->select(DB::raw(1))
                ->from('stock_ctl_user_profil')
                ->whereColumn('stock_ctl_user_profil.id_user', 'stock_ctl_permintaan.id_user_pemohon')
                ->where('stock_ctl_user_profil.id_approver', $user->id);
            })
            ->orderBy('tanggal_permintaan')
            ->get();

        // Tambahkan nama approver (atasan dari pemohon) untuk setiap permintaan
        foreach ($permintaan as $item) {
            // Ambil profil pemohon untuk mendapatkan id_approver
            $profil = UserProfil::where('id_user', $item->id_user_pemohon)->first();
            if ($profil && $profil->id_approver) {
                $approver = User::find($profil->id_approver);
                $item->approver_name = $approver ? $approver->name : '-';
            } else {
                $item->approver_name = '-';
            }
        }

        $pendingCount = $permintaan->count();
        return view('stock-ctl.approval.l1.index', compact('permintaan', 'pendingCount'));
    }

    public function approve($id)
    {
        Log::info('ApprovalL1 approve dipanggil', ['id' => $id, 'user' => Auth::id()]);
        DB::beginTransaction();
        try {
            $permintaan = Permintaan::with('areaKerja')->findOrFail($id);
            $this->authorizeL1($permintaan);

            $permintaan->update([
                'status'          => Permintaan::STATUS_PENDING_ADMIN,
                'approved_l1_by'  => Auth::id(),
                'approved_l1_at'  => now(),
            ]);

            // Kirim notifikasi ke admin dengan unit bisnis yang sama dengan pemohon
            $adminUsers = $this->getAdminUsersByArea($permintaan->id_area_kerja, $permintaan->id_user_pemohon);

            foreach ($adminUsers as $admin) {
                $admin->notify(new PermintaanMenungguAdmin($permintaan));
            }

            DB::commit();
            Log::info('Approval L1 berhasil', ['id' => $id]);
            return redirect()->route('stock-ctl.approval.l1.index')
                ->with('success', 'Permintaan disetujui dan diteruskan ke admin.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approval L1 gagal', [
                'error' => $e->getMessage(),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'id'    => $id
            ]);
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['alasan' => 'required|string']);

        try {
            $permintaan = Permintaan::findOrFail($id);
            $this->authorizeL1($permintaan);

            $permintaan->update([
                'status'           => Permintaan::STATUS_REJECTED,
                'rejected_by'      => Auth::id(),
                'rejected_at'      => now(),
                'alasan_tolak'     => $request->alasan, // ✅ sesuai struktur tabel
            ]);

            $permintaan->pemohon->notify(new PermintaanDitolak($permintaan));

            return redirect()->route('stock-ctl.approval.l1.index')
                ->with('success', 'Permintaan ditolak.');
        } catch (\Exception $e) {
            Log::error('Reject L1 gagal', ['error' => $e->getMessage(), 'id' => $id]);
            return back()->withErrors('Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    private function authorizeL1($permintaan)
    {
        $user = Auth::user();

        $isApprover = UserProfil::where('id_user', $permintaan->id_user_pemohon)
            ->where('id_approver', $user->id)
            ->exists();

        if (!$isApprover) {
            Log::warning('Otorisasi gagal: user bukan atasan', [
                'user_id'      => $user->id,
                'pemohon_id'   => $permintaan->id_user_pemohon
            ]);
            abort(403, 'Anda bukan atasan dari pemohon ini.');
        }

        if ($permintaan->status != Permintaan::STATUS_PENDING_L1) {
            abort(400, 'Permintaan sudah diproses.');
        }
    }

    /**
     * Mendapatkan daftar admin yang memiliki unit bisnis sama dengan pemohon
     * dan berada di area yang sama (opsional).
     */
private function getAdminUsersByArea($idAreaKerja, $idPemohon)
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
        ->where('stock_ctl_user_profil.id_bisnis_unit', $unitPemohon) // hanya filter unit
        ->pluck('users.id');
    

            \Log::info('Notifikasi dikirim ke admin', [
        'permintaan_id' => $idPemohon, // sebenarnya ini id permintaan, bukan id pemohon. Anda bisa meneruskan id permintaan dari parameter atau menyimpannya di variable.
        'unit_pemohon' => $unitPemohon,
        'admin_ids' => $adminUserIds->toArray()
    ]);
    
    return User::whereIn('id', $adminUserIds)->get();
}
}