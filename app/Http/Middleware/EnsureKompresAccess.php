<?php

namespace App\Http\Middleware;

use App\Models\AccessMenu;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKompresAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Anda harus login untuk mengakses modul ini.');
        }

        // Sesuaikan 'username' di bawah ini kalau kolom login user kamu
        // namanya beda (mis. $user->email).
        $access = AccessMenu::where('username', $user->username)->first();

        // Hanya kompres_access = 1 yang boleh masuk (tidak ada bypass superadmin).
        $allowed = $access && (bool) $access->kompres_access;

        if (! $allowed) {
            abort(403, 'Kamu tidak memiliki akses ke modul Kompres Foto & PDF.');
        }

        return $next($request);
    }
}
