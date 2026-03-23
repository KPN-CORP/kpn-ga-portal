<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingAccessController extends Controller
{
    public function index(Request $request)
    {
        $username = $request->username;

        // Ambil data dari api_emp_hcis
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

        // Ambil dari tb_pelanggan (opsional, jika ada user di luar api_emp_hcis)
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

        // Gabungkan dan unique berdasarkan username_pelanggan
        $users = $usersFromApi->concat($usersFromPelanggan)
            ->unique('username_pelanggan')
            ->sortBy('nama_pelanggan')
            ->values();

        // Nilai unik untuk dropdown filter (dari api_emp_hcis)
        $groupCompanies = $employees->pluck('group_company')->unique()->filter()->values();
        $officeAreas    = $employees->pluck('office_area')->unique()->filter()->values();
        $units          = $employees->pluck('unit')->unique()->filter()->values();

        // Data akses untuk user yang dipilih
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

        // Kolom tabel akses
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
        // sama seperti sebelumnya
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
}