<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\UserAccessExport;
use App\Exports\MultipleUserAccessExport;
use Maatwebsite\Excel\Facades\Excel;

class SettingAccessController extends Controller
{
    public function index(Request $request)
    {
        // Sama seperti sebelumnya (tidak berubah)
        $username = $request->username;

        $employees = DB::table('api_emp_hcis')
            ->select('employee_id', 'fullname', 'group_company', 'office_area', 'unit')
            ->get();

        $usersFromApi = $employees->map(function ($emp) {
            return (object) [
                'nama_pelanggan'     => $emp->fullname,
                'username_pelanggan' => $emp->employee_id,
                'group_company'      => $emp->group_company,
                'office_area'        => $emp->office_area,
                'unit'               => $emp->unit,
            ];
        });

        $usersFromPelanggan = DB::table('tb_pelanggan')
            ->select('nama_pelanggan', 'username_pelanggan')
            ->orderBy('nama_pelanggan')
            ->get()
            ->map(function ($p) {
                $p->group_company = null;
                $p->office_area = null;
                $p->unit = null;
                return $p;
            });

        $users = $usersFromApi->concat($usersFromPelanggan)
            ->unique('username_pelanggan')
            ->sortBy('nama_pelanggan')
            ->values();

        $groupCompanies = $employees->pluck('group_company')->unique()->filter()->values();
        $officeAreas    = $employees->pluck('office_area')->unique()->filter()->values();
        $units          = $employees->pluck('unit')->unique()->filter()->values();

        $dashData = null;
        $menuData = null;
        $selectedUserName = '';

        if ($username) {
            $dashData = DB::table('tb_access_dash')
                ->where('username_access', $username)
                ->first();

            $menuData = DB::table('tb_access_menu')
                ->where('username', $username)
                ->first();

            $selected = $users->firstWhere('username_pelanggan', $username);
            if ($selected) {
                $selectedUserName = $selected->nama_pelanggan . ' (' . $selected->username_pelanggan . ')';
            } else {
                $selectedUserName = $username;
            }
        }

        $dashCols = DB::select("SHOW COLUMNS FROM tb_access_dash");
        $menuCols = DB::select("SHOW COLUMNS FROM tb_access_menu");

        return view('setting-access.index', compact(
            'users',
            'groupCompanies',
            'officeAreas',
            'units',
            'username',
            'selectedUserName',
            'dashData',
            'dashCols',
            'menuData',
            'menuCols'
        ));
    }

    public function store(Request $request)
    {
        // Sama seperti sebelumnya
        $username = $request->username;

        $dashCols = DB::select("SHOW COLUMNS FROM tb_access_dash");
        $dashUpdate = [];
        foreach ($dashCols as $c) {
            if (in_array($c->Field, ['id_access', 'username_access', 'bu_access'])) continue;
            $dashUpdate[$c->Field] = isset($request->dash[$c->Field]) ? 1 : 0;
        }
        DB::table('tb_access_dash')->updateOrInsert(
            ['username_access' => $username],
            array_merge(['username_access' => $username], $dashUpdate)
        );

        $menuCols = DB::select("SHOW COLUMNS FROM tb_access_menu");
        $menuUpdate = [];
        foreach ($menuCols as $c) {
            if (in_array($c->Field, ['id', 'username'])) continue;
            $menuUpdate[$c->Field] = isset($request->menu[$c->Field]) ? 1 : 0;
        }
        DB::table('tb_access_menu')->updateOrInsert(
            ['username' => $username],
            array_merge(['username' => $username], $menuUpdate)
        );

        return redirect()->back()->with('success', 'Akses berhasil disimpan');
    }

    // Export single user (tetap)
    public function export(Request $request)
    {
        $username = $request->query('username');
        if (!$username) {
            return redirect()->back()->with('error', 'Username tidak ditemukan.');
        }
        return Excel::download(new UserAccessExport($username), 'user_access_' . $username . '.xlsx');
    }

    // Export multiple users berdasarkan filter
    public function exportAll(Request $request)
    {
        // Ambil data dari POST, bukan query string
        $usernames = [];
        if ($request->has('usernames')) {
            $usernames = json_decode($request->usernames, true);
        } else {
            // Fallback: gunakan filter yang dikirim
            $group = $request->input('group', '');
            $area  = $request->input('area', '');
            $unit  = $request->input('unit', '');
            $search = $request->input('search', '');

            $query = DB::table('api_emp_hcis')
                ->select('employee_id as username');
            
            if ($group) $query->where('group_company', $group);
            if ($area)  $query->where('office_area', $area);
            if ($unit)  $query->where('unit', $unit);
            if ($search && strlen($search) >= 3) {
                $query->where(function($q) use ($search) {
                    $q->where('fullname', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
                });
            }
            
            $usernames = $query->pluck('username')->toArray();
            
            // Cari juga dari tb_pelanggan jika diperlukan
            $queryPel = DB::table('tb_pelanggan')->select('username_pelanggan as username');
            if ($search && strlen($search) >= 3) {
                $queryPel->where('nama_pelanggan', 'like', "%{$search}%")
                        ->orWhere('username_pelanggan', 'like', "%{$search}%");
            }
            $usernames = array_unique(array_merge($usernames, $queryPel->pluck('username')->toArray()));
        }
        
        if (empty($usernames)) {
            return redirect()->back()->with('error', 'Tidak ada user yang dipilih untuk diexport.');
        }
        
        return Excel::download(new MultipleUserAccessExport($usernames), 'all_users_access_' . date('Y-m-d') . '.xlsx');
    }
}