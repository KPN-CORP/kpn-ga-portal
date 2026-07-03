<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CheckIDCardAccess
{
    public function handle(Request $request, Closure $next, string $type = 'list'): Response
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $username = $user->username;

        // Superadmin
        $isSuperAdmin = ($username == 'admin') ||
            DB::table('tb_access_menu')
                ->where('username', $username)
                ->where('proses_idcard', 1)
                ->exists();

        // Admin BU
        $adminBUIds = DB::table('request_idcard_accesbu')
            ->where('user_id', $user->id)
            ->pluck('bisnis_unit_id')
            ->toArray();
        $isAdminBU = !empty($adminBUIds);

        $allowed = false;

        switch ($type) {
            case 'list':
            case 'detail':
            case 'request':
                $allowed = true;
                break;
            case 'proses':
            case 'approve':
            case 'reject':
            case 'edit':
            case 'update':
                $allowed = $isSuperAdmin || $isAdminBU;
                break;
            case 'grafik':
            case 'report':
                $allowed = $isSuperAdmin || $isAdminBU;
                break;
            case 'nonaktifkan':
                $allowed = $isSuperAdmin;
                break;
            default:
                $allowed = false;
        }

        if (!$allowed) {
            // Redirect ke halaman no-access dengan pesan
            return redirect()->route('no-access')->with('error', 'Anda tidak memiliki akses untuk halaman ini.');
        }

        return $next($request);
    }
}