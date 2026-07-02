<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HsrmAccessMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user) return redirect('login');

        // Cek akses ke modul HSRM dari tb_access_dash (hanya untuk menampilkan menu)
        $access = $user->accessDash;
        if (!$access || ($access->hsr_dash != 1 && $access->hsr_admin_dash != 1)) {
            return response()->view('no-access', [], 403);
        }

        // Tentukan role dari hsrm_user_roles
        $role = $user->getHsrmRoleAttribute();

        if ($role === 'admin') {
            session(['hsrm_role' => 'admin']);
        } elseif ($role === 'pic') {
            session(['hsrm_role' => 'pic']);
        } else {
            // User tidak punya role di hsrm_user_roles → tolak akses
            return response()->view('no-access', [], 403);
        }

        return $next($request);
    }
}