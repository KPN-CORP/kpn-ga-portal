<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MultipleUserAccessExport implements FromCollection, WithHeadings
{
    protected $usernames;

    public function __construct(array $usernames)
    {
        $this->usernames = $usernames;
    }

    public function collection()
    {
        $rows = collect();

        foreach ($this->usernames as $username) {
            // Ambil data user
            $user = DB::table('api_emp_hcis')
                ->where('employee_id', $username)
                ->select('fullname', 'group_company', 'office_area', 'unit')
                ->first();

            if (!$user) {
                $user = DB::table('tb_pelanggan')
                    ->where('username_pelanggan', $username)
                    ->select('nama_pelanggan as fullname', DB::raw('NULL as group_company'), DB::raw('NULL as office_area'), DB::raw('NULL as unit'))
                    ->first();
            }

            $fullname = $user->fullname ?? '-';
            $group    = $user->group_company ?? '-';
            $area     = $user->office_area ?? '-';
            $unit     = $user->unit ?? '-';

            // Akses dashboard
            $dash = DB::table('tb_access_dash')
                ->where('username_access', $username)
                ->first();

            // Akses menu
            $menu = DB::table('tb_access_menu')
                ->where('username', $username)
                ->first();

            $row = [
                'Username' => $username,
                'Nama'     => $fullname,
                'Group Company' => $group,
                'Office Area'   => $area,
                'Unit'          => $unit,
            ];

            // Kolom dashboard
            $dashCols = DB::select("SHOW COLUMNS FROM tb_access_dash");
            foreach ($dashCols as $c) {
                if (in_array($c->Field, ['id_access', 'username_access', 'bu_access'])) continue;
                $row["DASH_" . $c->Field] = $dash->{$c->Field} ?? 0;
            }

            // Kolom menu
            $menuCols = DB::select("SHOW COLUMNS FROM tb_access_menu");
            foreach ($menuCols as $c) {
                if (in_array($c->Field, ['id', 'username'])) continue;
                $row["MENU_" . $c->Field] = $menu->{$c->Field} ?? 0;
            }

            $rows->push($row);
        }

        return $rows;
    }

    public function headings(): array
    {
        // Ambil contoh baris pertama untuk menentukan heading
        if ($this->usernames) {
            $firstUser = $this->usernames[0] ?? null;
            if ($firstUser) {
                $dash = DB::table('tb_access_dash')->where('username_access', $firstUser)->first();
                $menu = DB::table('tb_access_menu')->where('username', $firstUser)->first();

                $headings = ['Username', 'Nama', 'Group Company', 'Office Area', 'Unit'];

                $dashCols = DB::select("SHOW COLUMNS FROM tb_access_dash");
                foreach ($dashCols as $c) {
                    if (in_array($c->Field, ['id_access', 'username_access', 'bu_access'])) continue;
                    $headings[] = "DASH_" . $c->Field;
                }

                $menuCols = DB::select("SHOW COLUMNS FROM tb_access_menu");
                foreach ($menuCols as $c) {
                    if (in_array($c->Field, ['id', 'username'])) continue;
                    $headings[] = "MENU_" . $c->Field;
                }

                return $headings;
            }
        }
        return [];
    }
}