<?php

namespace App\Policies\budget_pol;

use App\Models\budget_m\BudgetItem;
use App\Support\budget_s\BudgetPeran;

/**
 * Pembungkus tipis di atas BudgetPeran. Controller memakai BudgetPeran langsung,
 * jadi policy ini opsional (boleh didaftarkan dengan Gate::policy bila ingin memakai @can).
 */
class BudgetItemPolicy
{
    public function lihat($user, BudgetItem $item): bool
    {
        return BudgetPeran::bisaLihat($item, $user);
    }

    public function ubah($user, BudgetItem $item): bool
    {
        return BudgetPeran::bisaUbahItem($item, $user);
    }

    public function isiBudget($user, BudgetItem $item, int $tahun): bool
    {
        return BudgetPeran::bisaIsiBudget($item, $tahun, $user);
    }

    public function inputTagihan($user, BudgetItem $item): bool
    {
        return BudgetPeran::bisaInputTagihan($item, $user);
    }
}
