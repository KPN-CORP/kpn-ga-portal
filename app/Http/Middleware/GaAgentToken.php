<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GaAgentToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = (string) config('gafiles.agent_token');
        abort_if($token === '', 503, 'Agent token belum dikonfigurasi');

        $given = (string) $request->bearerToken();
        if ($given === '' || ! hash_equals($token, $given)) {
            abort(401, 'Unauthorized');
        }

        $ips = config('gafiles.agent_allowed_ips', []);
        if (! empty($ips) && ! in_array($request->ip(), $ips, true)) {
            abort(403, 'IP tidak diizinkan');
        }

        return $next($request);
    }
}
