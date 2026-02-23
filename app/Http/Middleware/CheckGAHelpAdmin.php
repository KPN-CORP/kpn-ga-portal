<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckGAHelpAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Belum login (jaga-jaga)
        if (!$user) {
            return redirect()->route('login');
        }

        // Super admin bebas
        if ($user->username === 'admin') {
            return $next($request);
        }

        $hasAccess = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->where('ga_help_admin', 1)
            ->exists();

        if (!$hasAccess) {
            // ⬅️ INI KUNCINYA
            return redirect()->route('no-access');
        }

        return $next($request);
    }
}