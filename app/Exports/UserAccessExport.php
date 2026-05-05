<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UserAccessExport implements FromArray, WithHeadings
{
    protected $username;

    public function __construct($username)
    {
        $this->username = $username;
    }

    /**
     * Data yang akan diexport (satu baris untuk user yang dipilih).
     */
    public function array(): array
    {
        // Ambil data user dari api_emp_hcis (sesuaikan dengan kebutuhan)
        $user = DB::table('api_emp_hcis')
            ->where('employee_id', $this->username)
            ->select('fullname', 'group_company', 'office_area', 'unit')
            ->first();

        if (!$user) {
            // Coba cari di tb_pelanggan jika tidak ada di api_emp_hcis
            $user = DB::table('tb_pelanggan')
                ->where('username_pelanggan', $this->username)
                ->select('nama_pelanggan as fullname', DB::raw('NULL as group_company'), DB::raw('NULL as office_area'), DB::raw('NULL as unit'))
                ->first();
        }

        $fullname = $user->fullname ?? '-';
        $group    = $user->group_company ?? '-';
        $area     = $user->office_area ?? '-';
        $unit     = $user->unit ?? '-';

        // Ambil data akses dashboard
        $dash = DB::table('tb_access_dash')
            ->where('username_access', $this->username)
            ->first();

        // Ambil data akses menu
        $menu = DB::table('tb_access_menu')
            ->where('username', $this->username)
            ->first();

        // Baris data
        $row = [
            'Username' => $this->username,
            'Nama'     => $fullname,
            'Group Company' => $group,
            'Office Area'   => $area,
            'Unit'          => $unit,
        ];

        // Ambil kolom dashboard (kecuali id_access, username_access, bu_access)
        $dashCols = DB::select("SHOW COLUMNS FROM tb_access_dash");
        foreach ($dashCols as $c) {
            if (in_array($c->Field, ['id_access', 'username_access', 'bu_access'])) continue;
            $row["DASH_".$c->Field] = $dash->{$c->Field} ?? 0;
        }

        // Ambil kolom menu (kecuali id, username)
        $menuCols = DB::select("SHOW COLUMNS FROM tb_access_menu");
        foreach ($menuCols as $c) {
            if (in_array($c->Field, ['id', 'username'])) continue;
            $row["MENU_".$c->Field] = $menu->{$c->Field} ?? 0;
        }

        return [$row];
    }

    /**
     * Heading (judul kolom) untuk file Excel.
     */
    public function headings(): array
    {
        // Jika ingin heading dinamis, ambil dari array pertama (tapi array() bisa dipanggil)
        $data = $this->array();
        if (empty($data)) {
            return ['Tidak ada data'];
        }
        return array_keys($data[0]);
    }
}