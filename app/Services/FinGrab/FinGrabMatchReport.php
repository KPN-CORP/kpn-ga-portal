<?php

namespace App\Services\FinGrab;

use Illuminate\Support\Facades\DB;

/**
 * Daftar pengecekan kecocokan untuk halaman Kecocokan data:
 *  - email  : karyawan yang cocok via email, beserta pasangannya di HCIS
 *  - idname : cocok via ID, tetapi nama di Grab dan di HCIS berbeda (untuk mencari ID yang salah orang)
 *  - manual : koreksi ID manual yang tersimpan (hanya untuk admin Corporate)
 * Dipotong per Bisnis unit seperti FinGrabPayload.
 */
class FinGrabMatchReport
{
    private const LIMIT_EMAIL = 500;
    private const LIMIT_IDNAME = 300;

    public static function build(array $scope): array
    {
        $limitE = self::LIMIT_EMAIL;
        $limitI = self::LIMIT_IDNAME;

        $email = self::base($scope)
            ->join('api_emp_hcis as h', fn ($j) => $j->whereRaw('h.email = t.employee_email COLLATE utf8mb4_0900_ai_ci'))
            ->where('t.hcis_match', 'email')
            ->groupBy('t.employee_name', 't.employee_email', 'h.employee_id', 'h.fullname')
            ->selectRaw('t.employee_name AS nama_grab, t.employee_email AS email, h.employee_id AS id_hcis, h.fullname AS nama_hcis, COUNT(*) AS transaksi, SUM(t.amount) AS total')
            ->orderByDesc('transaksi')
            ->limit($limitE)
            ->get();

        $idname = self::base($scope)
            ->join('api_emp_hcis as h', fn ($j) => $j->whereRaw('h.employee_id = t.employee_id COLLATE utf8mb4_0900_ai_ci'))
            ->where('t.hcis_match', 'id')
            ->where('t.employee_id_status', '<>', 'manual')
            ->whereRaw('t.employee_name COLLATE utf8mb4_0900_ai_ci <> h.fullname')
            ->groupBy('t.employee_id', 't.employee_name', 'h.fullname')
            ->selectRaw('t.employee_id AS id_hcis, t.employee_name AS nama_grab, h.fullname AS nama_hcis, COUNT(*) AS transaksi, SUM(t.amount) AS total')
            ->orderByDesc('transaksi')
            ->limit($limitI)
            ->get();

        $manual = null;
        if ($scope['all']) {
            $trx = DB::table('fingrab_transactions')->where('employee_id_status', 'manual')
                ->selectRaw('employee_id, COUNT(*) AS c')->groupBy('employee_id')->pluck('c', 'employee_id');

            $manual = DB::table('fingrab_id_overrides as o')
                ->leftJoin('api_emp_hcis as h', fn ($j) => $j->whereRaw('h.employee_id = o.employee_id COLLATE utf8mb4_0900_ai_ci'))
                ->leftJoin('users as u', 'u.id', '=', 'o.created_by')
                ->orderByDesc('o.updated_at')
                ->get(['o.match_type', 'o.match_value', 'o.employee_id', 'h.fullname as nama_hcis', 'u.name as oleh', 'o.updated_at'])
                ->map(function ($r) use ($trx) {
                    $r->transaksi = (int) ($trx[$r->employee_id] ?? 0);
                    return $r;
                });
        }

        return [
            'email' => $email, 'email_cut' => $email->count() >= $limitE,
            'idname' => $idname, 'idname_cut' => $idname->count() >= $limitI,
            'manual' => $manual,
        ];
    }

    private static function base(array $scope)
    {
        $q = DB::table('fingrab_transactions as t');
        if (!$scope['all']) {
            $q->whereIn('t.id_bisnis_unit', $scope['ids']); // sama seperti FinGrabPayload
        }

        return $q;
    }
}