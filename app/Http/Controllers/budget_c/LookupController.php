<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\HcisPegawai;
use App\Models\User;
use Illuminate\Http\Request;

class LookupController extends Controller
{
    /** Cari user (nama / email / nomor karyawan) untuk menu Akses. */
    public function user(Request $r)
    {
        $q = trim((string) $r->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $users = User::query()
            ->where(fn ($x) => $x->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('employee_no', 'like', "%{$q}%"))
            ->orderBy('name')->limit(15)->get(['id', 'name', 'email', 'employee_no']);

        $peran = BudgetAkses::whereIn('user_id', $users->pluck('id'))->pluck('peran', 'user_id');

        return response()->json($users->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name ?: $u->email,
            'email' => $u->email,
            'employee_no' => $u->employee_no,
            'peran' => $peran->get($u->id),
        ])->values());
    }

    /** Daftar BU dari api_emp_hcis.group_company. */
    public function bu()
    {
        return response()->json(HcisPegawai::daftarBu());
    }

    /** Daftar PT dari api_emp_hcis.company_name, disaring per BU. */
    public function pt(Request $r)
    {
        return response()->json(HcisPegawai::daftarPt($r->query('bu')));
    }
}
