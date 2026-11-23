<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CheckSettingAccess
{
    public function handle($request, Closure $next)
    {
        $username = Auth::user()->username ?? Auth::user()->username_pelanggan ?? null;

        if (!$username) {
            return redirect()->route('no-access');
        }

        $access = DB::table('tb_access_dash')
            ->where('username_access', $username)
            ->value('settingan');

        if ((int)$access !== 1) {
            return redirect()->route('no-access');
        }

        return $next($request);
    }
}
