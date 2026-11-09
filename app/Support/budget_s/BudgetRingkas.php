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
            ->berlakuDi($tahun)
            ->with(['sub.kategori', 'deptHead', 'pic'])
            ->withSum(['bulan as budget' => fn ($b) => $b->where('tahun', $tahun)], 'nominal')
            ->withSum(['tagihan as realisasi' => fn ($t) => $t->where('tahun', $tahun)], 'total');

        if (!empty($filter['bu'])) {
            $q->where('bu', $filter['bu']);
        }
        if (!empty($filter['pt'])) {
            $q->where('pt', $filter['pt']);
        }
        if (!empty($filter['sub_id'])) {
            $q->where('sub_id', $filter['sub_id']);
        }
        // dept = id Dept Head; 0 = item tanpa Dept Head ("Umum"). Pakai isset karena '0' dianggap kosong oleh empty().
        if (isset($filter['dept']) && $filter['dept'] !== '') {
            $dept = (int) $filter['dept'];
            $dept > 0
                ? $q->where('dept_head_user_id', $dept)
                : $q->where(fn ($w) => $w->whereNull('dept_head_user_id')->orWhere('dept_head_user_id', 0));
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
                'id' => $kat->id,
                'urut' => $kat->urut,
                'nama' => $kat->nama,
                'kunci' => $kat->kunciUrut(),
                'budget' => $b,
                'realisasi' => $r,
                'persen' => $b > 0 ? round($r / $b * 100, 1) : null,
                'status' => BudgetStatus::dari($b, $r),
                'items' => $g->sortBy('nama')->values(),
            ];
        })->sortBy('kunci')->values();
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

    public static function ptTersedia($user): array
    {
        return BudgetItem::terlihatOleh($user)->whereNotNull('pt')->where('pt', '<>', '')
            ->distinct()->orderBy('pt')->pluck('pt')->all();
    }

    /**
     * Pilihan filter yang saling singkron (Kategori, BU, PT): tiap daftar hanya berisi nilai yang masih punya item
     * setelah dua filter lainnya diterapkan. Pilihan yang sedang aktif selalu ikut ditampilkan.
     *
     * @return array{kategori: array<int,string>, sub: array<int,string>, bu: array<int,string>, pt: array<int,string>}
     */
    public static function opsiFilter($user, int $tahun, array $f): array
    {
        $baris = BudgetItem::terlihatOleh($user)->berlakuDi($tahun)->with('sub:id,kategori_id')->get(['id', 'sub_id', 'bu', 'pt']);

        return self::opsiDariItem($baris, $f);
    }

    /** Terapkan filter (bu, pt, kategori_id, sub_id) pada item yang sudah dimuat (item harus membawa relasi sub). */
    public static function saring(Collection $items, array $f): Collection
    {
        return $items->filter(fn ($i) =>
            (empty($f['bu']) || (string) $i->bu === (string) $f['bu'])
            && (empty($f['pt']) || (string) $i->pt === (string) $f['pt'])
            && (empty($f['kategori_id']) || (string) ($i->sub->kategori_id ?? '') === (string) $f['kategori_id'])
            && (empty($f['sub_id']) || (string) $i->sub_id === (string) $f['sub_id'])
        )->values();
    }

    /** Sama dengan opsiFilter, tapi dari item yang sudah ada di memori (tanpa query tambahan). */
    public static function opsiDariItem(Collection $baris, array $f): array
    {
        $kat = (string) ($f['kategori_id'] ?? '');
        $sb = (string) ($f['sub_id'] ?? '');
        $bu = (string) ($f['bu'] ?? '');
        $pt = (string) ($f['pt'] ?? '');

        // $abaikan = filter yang sedang dihitung pilihannya (tidak ikut membatasi dirinya sendiri)
        // Kategori adalah induk Sub, jadi pilihan Kategori tidak dibatasi oleh Sub yang dipilih
        $cocok = fn ($i, string $abaikan) =>
            ($abaikan === 'kategori' || $kat === '' || (string) ($i->sub->kategori_id ?? '') === $kat)
            && (in_array($abaikan, ['kategori', 'sub'], true) || $sb === '' || (string) $i->sub_id === $sb)
            && ($abaikan === 'bu' || $bu === '' || (string) $i->bu === $bu)
            && ($abaikan === 'pt' || $pt === '' || (string) $i->pt === $pt);

        $daftar = function (string $abaikan, callable $nilai, string $aktif) use ($baris, $cocok) {
            $v = $baris->filter(fn ($i) => $cocok($i, $abaikan))->map($nilai)
                ->filter(fn ($x) => $x !== null && $x !== '')->map(fn ($x) => (string) $x)->unique()->values()->all();
            if ($aktif !== '' && !in_array($aktif, $v, true)) {
                $v[] = $aktif;
            }
            sort($v, SORT_NATURAL | SORT_FLAG_CASE);
            return $v;
        };

        return [
            'kategori' => $daftar('kategori', fn ($i) => $i->sub->kategori_id ?? null, $kat),
            'sub' => $daftar('sub', fn ($i) => $i->sub_id, $sb),
            'bu' => $daftar('bu', fn ($i) => $i->bu, $bu),
            'pt' => $daftar('pt', fn ($i) => $i->pt, $pt),
        ];
    }
}
