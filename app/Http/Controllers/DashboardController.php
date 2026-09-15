<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\DashboardBadgeService;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard dengan menu berdasarkan hak akses
     */
    public function index()
    {
        $user = Auth::user();
        
        // Ambil data akses dari tabel tb_access_dash
        $access = DB::table('tb_access_dash')
            ->where('username_access', $user->username)
            ->first();

        // Hitung total akses
        $totalAccess = 0;
        if ($access) {
            $modules = [
                'idcard_dash' => $access->idcard_dash ?? 0,
                'messenger_dash' => $access->messenger_dash ?? 0,
                'ma_room_dash' => $access->ma_room_dash ?? 0,
                'receipt_dash' => $access->receipt_dash ?? 0,
                'employees_dash' => $access->employees_dash ?? 0,
                'reports_dash' => $access->reports_dash ?? 0
            ];

            foreach ($modules as $module => $value) {
                $totalAccess += ($value == 1) ? 1 : 0;
                // Set nilai ke object access untuk dipakai di view
                $access->$module = $value;
            }
        } else {
            // Buat object akses kosong untuk menghindari error
            $access = (object) [
                'username_access' => $user->username,
                'bu_access' => 'No Department',
                'idcard_dash' => 0,
                'messenger_dash' => 0,
                'ma_room_dash' => 0,
                'receipt_dash' => 0,
                'employees_dash' => 0,
                'reports_dash' => 0
            ];
            $totalAccess = 0;
        }

        $menuBadges = DashboardBadgeService::computeAll($user);

        return view('dashboard', compact('access', 'totalAccess', 'menuBadges'));
    }

    /**
     * API untuk mendapatkan data akses
     */
    public function getAccessData()
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $user = Auth::user();
        $access = DB::table('tb_access_dash')
            ->where('username_access', $user->username)
            ->first();

        if (!$access) {
            return response()->json([
                'success' => false,
                'message' => 'Data akses tidak ditemukan untuk user: ' . $user->username
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'username' => $access->username_access,
                'bu_access' => $access->bu_access,
                'modules' => [
                    'messenger' => (bool) ($access->messenger_dash ?? 0),
                    'mailing' => (bool) ($access->ma_room_dash ?? 0),
                    'trackreceipt' => (bool) ($access->receipt_dash ?? 0),
                    'idcard' => (bool) ($access->idcard_dash ?? 0),
                    'employees' => (bool) ($access->employees_dash ?? 0),
                    'reports' => (bool) ($access->reports_dash ?? 0),
                ]
            ]
        ]);
    }
}