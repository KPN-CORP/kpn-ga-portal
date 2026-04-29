<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsDriver
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (!$user || !$user->isDriver()) {
            abort(403, 'Anda tidak memiliki akses sebagai driver.');
        }
        return $next($request);
    }
}