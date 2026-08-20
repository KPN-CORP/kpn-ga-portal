<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class MobileOnly
{
    /**
     * Halaman ini butuh kamera HP (foto struk/odometer BBM), jadi akses dari
     * laptop/desktop diblokir. Deteksi berdasarkan User-Agent browser.
     */
    public function handle(Request $request, Closure $next)
    {
        $userAgent = $request->userAgent() ?? '';

        $isMobile = preg_match('/(android|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile)/i', $userAgent);

        if (!$isMobile) {
            abort(403, 'Halaman ini hanya bisa diakses lewat HP (mobile), tidak bisa dari laptop/PC.');
        }

        return $next($request);
    }
}
