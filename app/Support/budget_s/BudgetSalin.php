<?php

namespace App\Support\budget_s;

use App\Models\budget_m\BudgetBulan;
use App\Models\budget_m\BudgetItem;
use Illuminate\Support\Facades\DB;

/** Salin tahun lalu: HANYA menyalin baris budget_bulan. Item tidak disalin (item dipakai ulang lintas tahun). */
class BudgetSalin
{
    public static function jalankan(int $dari, int $ke, $user, bool $timpa = false): int
    {
        $items = BudgetItem::terlihatOleh($user)->where('aktif', true)->get()
            ->filter(fn ($i) => BudgetPeran::bisaIsiBudget($i, $ke, $user));

        $jumlah = 0;
        DB::transaction(function () use ($items, $dari, $ke, $timpa, &$jumlah) {
            foreach ($items as $item) {
                $baris = BudgetBulan::query()->where('item_id', $item->id)->where('tahun', $dari)->get();
                foreach ($baris as $b) {
                    $sudah = BudgetBulan::query()
                        ->where('item_id', $item->id)->where('tahun', $ke)->where('bulan', $b->bulan)->exists();
                    if ($sudah && !$timpa) {
                        continue;
                    }
                    BudgetBulan::updateOrCreate(
                        ['item_id' => $item->id, 'tahun' => $ke, 'bulan' => $b->bulan],
                        ['nominal' => $b->nominal]
                    );
                    $jumlah++;
                }
            }
        });
        return $jumlah;
    }
}
