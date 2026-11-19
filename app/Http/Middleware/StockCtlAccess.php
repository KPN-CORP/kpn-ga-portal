<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\AccessMenu; // <-- Gunakan model yang sudah ada
use App\Models\StockCtl\UserProfil; // pastikan model ini ada

class StockCtlAccess
{
    public function handle($request, Closure $next, $level = null)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect('/login');
        }

        // Ambil akses dari AccessMenu (tb_access_menu)
        $access = AccessMenu::where('username', $user->username)->first();
        if (!$access) {
            abort(403, 'Akses ATK tidak ditemukan.');
        }

        // Tentukan level berdasarkan parameter atau urutan prioritas
        $isSuper = $access->stock_ctl_superadmin ?? false;
        $isAdmin = $access->stock_ctl_admin ?? false;
        $isUser  = $access->stock_ctl_user ?? false;

        if ($level === 'superadmin' && !$isSuper) {
            abort(403, 'Akses superadmin ditolak.');
        }
        if ($level === 'admin' && !$isAdmin && !$isSuper) {
            abort(403, 'Akses admin ditolak.');
        }
        if ($level === 'user' && !$isUser && !$isAdmin && !$isSuper) {
            abort(403, 'Akses user ditolak.');
        }

        // Simpan data akses dan profil ke session
        $profil = UserProfil::where('id_user', $user->id)->first();
        session([
            'stock_ctl_access' => [
                'is_super' => $isSuper,
                'is_admin' => $isAdmin,
                'is_user'  => $isUser,
                'id_area_kerja' => $profil->id_area_kerja ?? null,
                'id_bisnis_unit' => $profil->id_bisnis_unit ?? null,
                'id_approver' => $profil->id_approver ?? null,
            ]
        ]);

        return $next($request);
    }
}