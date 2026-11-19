<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckMessengerAccess
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Belum login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();

        // Data akses tidak ada
        if (!$access) {
            return response()->view('no-access', [], 403);
        }

        // Cek permission spesifik
        if (isset($access->$permission) && (int)$access->$permission === 1) {
            return $next($request);
        }

        // Tidak ada permission yang cocok
        return response()->view('no-access', [], 403);
    }
}