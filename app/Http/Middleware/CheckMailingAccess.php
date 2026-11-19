<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckMailingAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $accessType = 'index'): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $username = Auth::user()->username;
        
        $accessMenu = DB::table('tb_access_menu')
            ->where('username', $username)
            ->first();

        if (!$accessMenu) {
            abort(403, 'Anda tidak memiliki akses ke modul mailing');
        }

        return $next($request);
    }
}