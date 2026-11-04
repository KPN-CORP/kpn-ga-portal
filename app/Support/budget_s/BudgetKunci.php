<?php

namespace App\Support\budget_s;

use App\Models\budget_m\BudgetTahun;

class BudgetKunci
{
    public static function kunci(int $tahun, $user): void
    {
        BudgetTahun::query()->where('tahun', $tahun)->update([
            'terkunci' => true,
            'dikunci_oleh' => $user->id,
            'dikunci_pada' => now(),
        ]);
    }

    public static function buka(int $tahun): void
    {
        BudgetTahun::query()->where('tahun', $tahun)->update([
            'terkunci' => false,
            'dikunci_oleh' => null,
            'dikunci_pada' => null,
        ]);
    }
}
