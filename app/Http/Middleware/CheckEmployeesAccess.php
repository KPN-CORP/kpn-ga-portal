<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckEmployeesAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, $permission)
    {
        $user = Auth::user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // LOGIC SANGAT SEDERHANA:
        // 1. Cek di tb_access_menu berdasarkan username user yang login
        // 2. Jika permission = 1 → izinkan, jika = 0 → tolak
        
        $username = $user->username_pelanggan ?? $user->username ?? $user->email;
        
        if (!$username) {
            return $this->showNoAccess($permission);
        }

        // Cek permission di tb_access_menu
        $access = DB::table('tb_access_menu')
            ->where('username', $username)
            ->first();

        if (!$access) {
            // Tidak ada record untuk username ini
            return $this->showNoAccess($permission);
        }

        // Cek apakah permission = 1
        if (!isset($access->$permission) || $access->$permission != 1) {
            // Permission = 0 → tolak akses
            return $this->showNoAccess($permission);
        }

        // Permission = 1 → izinkan akses
        return $next($request);
    }
    
    /**
     * Show no access page
     */
    private function showNoAccess($permission)
    {
        $pageNames = [
            'emp_index' => 'Employees List Page',
            'emp_show' => 'Employee Details Page', 
            'emp_edit' => 'Edit Employee Page',
        ];
        
        return response()->view('no-access', [
            'pageName' => $pageNames[$permission] ?? 'Employees Page',
            'message' => "Access denied. You don't have permission to view this page."
        ], 403);
    }
}