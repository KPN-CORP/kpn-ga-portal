<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckApartemenAccess
{
    public function handle(Request $request, Closure $next, string $access)
    {
        $user = Auth::user();

        // jika belum login
        if (!$user) {
            return redirect()->route('login');
        }

        // ambil akses berdasarkan username
        $accessRow = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();

        // jika data akses tidak ada
        if (!$accessRow) {
            return redirect()->route('no-access');
        }

        // jika kolom akses tidak ada / nilainya bukan 1
        if (!isset($accessRow->$access) || (int)$accessRow->$access !== 1) {
            return redirect()->route('no-access');
        }

        return $next($request);
    }
}
