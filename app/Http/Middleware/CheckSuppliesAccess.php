<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckSuppliesAccess
{
    public function handle(Request $request, Closure $next, $role = 'user')
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();

        if (!$access) {
            abort(403, 'Akses Supplies tidak ditemukan.');
        }

        // Untuk role user, cek supplies_user atau supplies_admin
        if ($role === 'user') {
            if (!$access->supplies_user && !$access->supplies_admin) {
                abort(403, 'Akses user Supplies diperlukan.');
            }
        }
        // Untuk role admin
        elseif ($role === 'admin') {
            if (!$access->supplies_admin) {
                abort(403, 'Akses admin Supplies diperlukan.');
            }
        }

        session([
            'supplies_access' => [
                'is_user' => (bool)$access->supplies_user,
                'is_admin' => (bool)$access->supplies_admin,
                'username' => $user->username,
                'user_id' => $user->id,
            ]
        ]);

        return $next($request);
    }
}