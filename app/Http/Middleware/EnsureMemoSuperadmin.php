<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Kunci halaman kelola Tim & Setting Nomor Memo supaya HANYA superadmin
 * (memo_superadmin = 1 di tb_access_menu) yang bisa akses.
 * User dan admin biasa akan ditolak (403), sesuai requirement:
 * "yang bikin ini superadmin, user dan admin tidak bisa bikin".
 *
 * Daftarkan di app/Http/Kernel.php:
 *   'memo.superadmin' => \App\Http\Middleware\EnsureMemoSuperadmin::class,
 */
class EnsureMemoSuperadmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || !auth()->user()->isMemoSuperadmin()) {
            abort(403, 'Hanya superadmin e-Memo yang bisa mengakses halaman ini.');
        }

        return $next($request);
    }
}
