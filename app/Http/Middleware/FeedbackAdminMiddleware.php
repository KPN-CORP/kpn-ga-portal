<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FeedbackAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isFeedbackAdmin()) {
            abort(403, 'Unauthorized.');
        }
        return $next($request);
    }
}