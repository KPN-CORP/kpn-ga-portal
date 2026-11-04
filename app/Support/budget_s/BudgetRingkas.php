<?php

namespace App\Support\budget_s;

use App\Models\budget_m\BudgetBulan;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetTagihan;
use Illuminate\Support\Collection;

/** Penjumlahan budget dan realisasi (realisasi = jumlah total dari budget_tagihan). */
class BudgetRingkas
{
    public static function perItem($user, int $tahun, array $filter = []): Collection
    {
        $q = BudgetItem::terlihatOleh($user)
            ->where('aktif', true)
            ->with(['sub.kategori', 'deptHead', 'pic'])
            ->withSum(['bulan as budget' => fn ($b) => $b->where('tahun', $tahun)], 'nominal')
            ->withSum(['tagihan as realisasi' => fn ($t) => $t->where('tahun', $tahun)], 'total');

        if (!empty($filter['bu'])) {
            $q->where('bu', $filter['bu']);
        }
        if (!empty($filter['sub_id'])) {
            $q->where('sub_id', $filter['sub_id']);
        }
        if (!empty($filter['kategori_id'])) {
            $q->whereHas('sub', fn ($s) => $s->where('kategori_id', $filter['kategori_id']));
        }

        return $q->orderBy('nama')->get()->each(function ($i) {
            $i->budget = (float) $i->budget;
            $i->realisasi = (float) $i->realisasi;
            $i->persen = $i->budget > 0 ? round($i->realisasi / $i->budget * 100, 1) : null;
            $i->status = BudgetStatus::dari($i->budget, $i->realisasi);
        });
    }

    public static function kpi(Collection $items): array
    {
        $budget = (float) $items->sum('budget');
        $real = (float) $items->sum('realisasi');
        return [
            'jumlah' => $items->count(),
            'budget' => $budget,
            'realisasi' => $real,
            'sisa' => $budget - $real,
            'persen' => $budget > 0 ? round($real / $budget * 100, 1) : null,
            'melebihi' => $items->filter(fn ($i) => $i->status['kode'] === 'lebih')->count(),
            'status' => BudgetStatus::dari($budget, $real),
        ];
    }

    public static function perKategori(Collection $items): Collection
    {
        return $items->groupBy(fn ($i) => $i->sub->kategori_id)->map(function ($g) {
            $kat = $g->first()->sub->kategori;
            $b = (float) $g->sum('budget');
            $r = (float) $g->sum('realisasi');
            return [
                'urut' => $kat->urut,
                'nama' => $kat->nama,
                'budget' => $b,
                'realisasi' => $r,
                'persen' => $b > 0 ? round($r / $b * 100, 1) : null,
                'status' => BudgetStatus::dari($b, $r),
                'items' => $g->sortBy('nama')->values(),
            ];
        })->sortBy('urut')->values();
    }

    /** @return array{budget: array<int,float>, realisasi: array<int,float>} indeks 1..12 */
    public static function bulanan(array $itemIds, int $tahun): array
    {
        $b = array_fill(1, 12, 0.0);
        $r = array_fill(1, 12, 0.0);
        if ($itemIds) {
            BudgetBulan::query()->whereIn('item_id', $itemIds)->where('tahun', $tahun)
                ->selectRaw('bulan, SUM(nominal) AS t')->groupBy('bulan')->pluck('t', 'bulan')
                ->each(function ($v, $k) use (&$b) {
                    $b[(int) $k] = (float) $v;
                });
            BudgetTagihan::query()->whereIn('item_id', $itemIds)->where('tahun', $tahun)
                ->selectRaw('bulan, SUM(total) AS t')->groupBy('bulan')->pluck('t', 'bulan')
                ->each(function ($v, $k) use (&$r) {
                    $r[(int) $k] = (float) $v;
                });
        }
        return ['budget' => $b, 'realisasi' => $r];
    }

    public static function buTersedia($user): array
    {
        return BudgetItem::terlihatOleh($user)->whereNotNull('bu')->where('bu', '<>', '')
            ->distinct()->orderBy('bu')->pluck('bu')->all();
    }
}
