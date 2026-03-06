<?php
// app/Http/Middleware/CheckPublicAccess.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckPublicAccess
{
    public function handle(Request $request, Closure $next)
    {
        // Cek user agent mencurigakan
        $userAgent = $request->userAgent();
        $blockedAgents = ['sqlmap', 'nikto', 'nmap', 'zgrab'];
        
        foreach ($blockedAgents as $agent) {
            if (stripos($userAgent, $agent) !== false) {
                Log::warning('Blocked suspicious user agent', [
                    'agent' => $userAgent,
                    'ip' => $request->ip()
                ]);
                abort(403);
            }
        }
        
        // Cek referer (opsional)
        if ($request->isMethod('post') && !$request->is('apartemen/public/verify')) {
            $referer = $request->headers->get('referer');
            if (!$referer || !str_contains($referer, $request->getHost())) {
                Log::warning('Invalid referer', [
                    'referer' => $referer,
                    'ip' => $request->ip()
                ]);
                abort(403);
            }
        }
        
        return $next($request);
    }
}