<?php

namespace App\Support\budget_s;

class BudgetFormat
{
    public const BULAN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    public const BULAN_SINGKAT = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public static function rupiah($n): string
    {
        return 'Rp ' . number_format((float) $n, 0, ',', '.');
    }

    /** Angka untuk isi input: tanpa nol di belakang koma. */
    public static function angka($n): string
    {
        if ($n === null || $n === '') {
            return '';
        }
        $f = (float) $n;
        return $f == (int) $f ? (string) (int) $f : (string) $f;
    }

    public static function persen($p): string
    {
        return $p === null ? '-' : number_format((float) $p, 1, ',', '.') . '%';
    }
}
