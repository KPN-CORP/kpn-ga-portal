<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Founddesk\FounddeskAccess as FounddeskAccessModel;

class FounddeskAccess
{
    public function handle($request, Closure $next)
    {
        // WAJIB LOGIN
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $username = Auth::user()->username;

        // TIDAK PUNYA AKSES FOUNDESK
        if (!FounddeskAccessModel::hasAccess($username)) {
            return response()
                ->view('no-access', [
                    'title' => 'Akses Ditolak',
                    'message' => 'Anda tidak memiliki akses ke halaman Founddesk'
                ], 403);
        }

        return $next($request);
    }
}