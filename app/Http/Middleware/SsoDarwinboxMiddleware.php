<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class SsoDarwinboxMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::channel('sso')->info('SSO MIDDLEWARE HIT', [
            'time'        => now()->toDateTimeString(),
            'ip'          => $request->ip(),
            'url'         => $request->fullUrl(),
            'has_data'    => $request->has('data'),
            'query_keys'  => array_keys($request->query()),
            'referer'     => $request->header('referer'),
            'user_agent'  => $request->userAgent(),
        ]);

        /**
         * ✅ WAJIB ADA PARAMETER data
         */
        if (!$request->has('data')) {
            Log::channel('sso')->error('SSO FAILED: data parameter missing');

            return redirect()->route('login')->with([
                'error' => 'SSO gagal: data tidak ditemukan',
                'sso'   => false,
            ]);
        }

        /**
         * ✅ data BOLEH APA SAJA
         * ❌ JANGAN base64_decode di sini
         * ❌ JANGAN decrypt di sini
         */
        $data = $request->query('data');

        if (!is_string($data) || strlen($data) < 20) {
            Log::channel('sso')->error('SSO FAILED: invalid data length', [
                'length' => strlen((string) $data),
            ]);

            return redirect()->route('login')->with([
                'error' => 'SSO gagal: payload tidak valid',
                'sso'   => false,
            ]);
        }

        return $next($request);
    }
}
