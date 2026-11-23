<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckModuleAccess
{
    private $moduleFieldMap = [
        'messenger' => 'messenger_dash',
        'mailing' => 'ma_room_dash',
        'trackreceipt' => 'receipt_dash',
        'idcard' => 'idcard_dash',
        'employees' => 'employees_dash',
        'reports' => 'reports_dash'
    ];

    /**
     * Handle incoming request
     */
    public function handle(Request $request, Closure $next, string $module)
    {
        // Cek jika user tidak login
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $username = Auth::user()->username;
        $access = DB::table('tb_access_dash')->where('username_access', $username)->first();

        // Jika data akses tidak ditemukan
        if (!$access) {
            return redirect()->route('dashboard.index')
                ->with('error', 'Profil akses tidak ditemukan.');
        }

        // Jika module tidak valid
        if (!array_key_exists($module, $this->moduleFieldMap)) {
            return redirect()->route('dashboard.index')
                ->with('error', 'Module tidak valid.');
        }

        // Cek akses module
        $field = $this->moduleFieldMap[$module];
        if (!isset($access->$field) || $access->$field != 1) {
            return redirect()->route('dashboard.index')
                ->with('error', 'Akses ditolak untuk module: ' . ucfirst($module));
        }

        return $next($request);
    }
}