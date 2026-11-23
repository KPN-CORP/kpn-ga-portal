<?php

namespace App\Support\budget_s;

use App\Models\budget_m\BudgetBulan;
use App\Models\budget_m\BudgetTagihan;
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

    /**
     * Tahun terkunci yang masih memuat data item ini (budget bulanan atau tagihan).
     * Item seperti ini tidak boleh dihapus maupun dinonaktifkan, karena riwayat tahun itu akan hilang dari laporan.
     * @return int[]
     */
    public static function tahunTerkunciItem(int $itemId): array
    {
        $kunci = BudgetTahun::query()->where('terkunci', true)->pluck('tahun')->map(fn ($v) => (int) $v)->all();
        if (!$kunci) {
            return [];
        }
        $a = BudgetBulan::query()->where('item_id', $itemId)->whereIn('tahun', $kunci)->distinct()->pluck('tahun');
        $b = BudgetTagihan::query()->where('item_id', $itemId)->whereIn('tahun', $kunci)->distinct()->pluck('tahun');
        return $a->merge($b)->map(fn ($v) => (int) $v)->unique()->sort()->values()->all();
    }

    /** Versi massal untuk daftar item: id item => true bila punya data di tahun terkunci. */
    public static function itemIdTerkunci(array $itemIds): array
    {
        $kunci = BudgetTahun::query()->where('terkunci', true)->pluck('tahun')->all();
        if (!$kunci || !$itemIds) {
            return [];
        }
        $a = BudgetBulan::query()->whereIn('item_id', $itemIds)->whereIn('tahun', $kunci)->distinct()->pluck('item_id');
        $b = BudgetTagihan::query()->whereIn('item_id', $itemIds)->whereIn('tahun', $kunci)->distinct()->pluck('item_id');
        return $a->merge($b)->unique()->mapWithKeys(fn ($id) => [(int) $id => true])->all();
    }
}
