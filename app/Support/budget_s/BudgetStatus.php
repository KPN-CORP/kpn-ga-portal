<?php

namespace App\Support\budget_s;

class BudgetStatus
{
    /** < 80% aman, 80-100% waspada, > 100% melebihi. */
    public static function dari(float $budget, float $realisasi): array
    {
        if ($budget <= 0) {
            return $realisasi > 0
                ? ['kode' => 'lebih', 'label' => 'Melebihi budget', 'kelas' => 'b-er']
                : ['kode' => 'kosong', 'label' => 'Belum ada budget', 'kelas' => 'b-mu'];
        }
        $p = $realisasi / $budget * 100;
        if ($p > 100) {
            return ['kode' => 'lebih', 'label' => 'Melebihi budget', 'kelas' => 'b-er'];
        }
        if ($p >= 80) {
            return ['kode' => 'waspada', 'label' => 'Hampir habis', 'kelas' => 'b-wn'];
        }
        return ['kode' => 'aman', 'label' => 'Aman', 'kelas' => 'b-ok'];
    }
}
