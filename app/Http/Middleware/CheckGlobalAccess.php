<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\AccessDash;

class CheckGlobalAccess
{
    /**
     * Cek apakah user memiliki minimal 1 akses module
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $username = Auth::user()->username;
        $access = AccessDash::where('username_access', $username)->first();

        // Jika tidak punya akses sama sekali, redirect ke halaman no-access
        if (!$access || $access->totalAccessCount() === 0) {
            if ($request->route()->getName() !== 'no-access') {
                return redirect()->route('no-access');
            }
        }

        return $next($request);
    }
}