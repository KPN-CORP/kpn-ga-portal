<?php

namespace App\Support\budget_s;

use App\Models\budget_m\BudgetBulan;
use App\Models\budget_m\BudgetItem;
use Illuminate\Support\Facades\DB;

/**
 * Salin dari tahun lain: mengambil item (beserta subkategori dan kategorinya) ke tahun tujuan, dan bila diminta
 * ikut menyalin angka budget bulanan. Tahun baru kosong sampai langkah ini dijalankan.
 * Bila item diganti nama (item baru punya item_asal_id), item baru ikut terambil dan menerima angka dari item lamanya.
 * Pilihan kategori / sub / item membatasi apa yang diambil; kosong = semua yang Anda kelola.
 */
class BudgetSalin
{
    /** @return array{item: int, baris: int} item = jumlah item baru terdaftar di tahun tujuan, baris = angka bulanan yang disalin */
    public static function jalankan(int $dari, int $ke, $user, bool $timpa = false, array $pilih = [], bool $salinAngka = true): array
    {
        $tampilDari = BudgetItem::berlakuDi($dari)->pluck('id')->flip();
        $kat = $pilih['kategori_id'] ?? null;
        $sub = $pilih['sub_id'] ?? null;
        $itemPilih = $pilih['item_id'] ?? null;

        $items = BudgetItem::terlihatOleh($user)->rentangDi($ke)->with('sub')
            ->when($kat, fn ($q, $v) => $q->whereHas('sub', fn ($s) => $s->where('kategori_id', $v)))
            ->when($sub, fn ($q, $v) => $q->where('sub_id', $v))
            ->get()
            ->filter(fn ($i) => BudgetPeran::bisaIsiBudget($i, $ke, $user))
            ->filter(fn ($i) => self::rantai($i)->contains(fn ($x) => $tampilDari->has($x->id)))
            ->filter(fn ($i) => !$itemPilih || self::rantai($i)->contains(fn ($x) => (int) $x->id === (int) $itemPilih));

        $jumItem = 0;
        $jumlah = 0;
        $ditimpa = []; // angka lama yang tertimpa: {item_id: {bulan: nominal_lama}}
        DB::transaction(function () use ($items, $dari, $ke, $timpa, $salinAngka, &$jumItem, &$jumlah, &$ditimpa) {
            foreach ($items as $item) {
                if ($item->daftarkanDi($ke)) {
                    $jumItem++;
                }
                if (!$salinAngka) {
                    continue;
                }
                foreach (self::sumber($item, $dari) as $b) {
                    $ada = BudgetBulan::query()
                        ->where('item_id', $item->id)->where('tahun', $ke)->where('bulan', $b->bulan)->first();
                    if ($ada && !$timpa) {
                        continue;
                    }
                    if ($ada && (float) $ada->nominal != (float) $b->nominal) {
                        $ditimpa[$item->id][$b->bulan] = (float) $ada->nominal;
                    }
                    BudgetBulan::updateOrCreate(
                        ['item_id' => $item->id, 'tahun' => $ke, 'bulan' => $b->bulan],
                        ['nominal' => $b->nominal]
                    );
                    $jumlah++;
                }
            }
        });

        BudgetCatat::tulis(
            'salin', 'budget_bulan', null,
            'Salin ' . $dari . ' ke ' . $ke . ': ' . $jumItem . ' item baru' . ($salinAngka ? ', ' . $jumlah . ' baris budget' : ', tanpa angka') . ($timpa ? ' (menimpa)' : ''),
            $ditimpa ?: null,
            ['dari' => $dari, 'ke' => $ke, 'timpa' => $timpa, 'angka' => $salinAngka, 'item' => $jumItem, 'baris' => $jumlah, 'pilih' => array_filter($pilih)],
            ['tahun' => $ke]
        );

        return ['item' => $jumItem, 'baris' => $jumlah];
    }

    /** Item itu sendiri + pendahulunya (item_asal_id), maksimal 6 tingkat. */
    protected static function rantai(BudgetItem $item)
    {
        $hasil = collect();
        $cur = $item;
        for ($n = 0; $n < 6 && $cur; $n++) {
            $hasil->push($cur);
            $cur = $cur->item_asal_id ? BudgetItem::find($cur->item_asal_id) : null;
        }
        return $hasil;
    }

    /** Angka tahun $dari milik item ini; kalau kosong, telusuri item pendahulunya (maks 5 tingkat). */
    protected static function sumber(BudgetItem $item, int $dari)
    {
        foreach (self::rantai($item) as $cur) {
            $baris = BudgetBulan::query()->where('item_id', $cur->id)->where('tahun', $dari)->get();
            if ($baris->isNotEmpty()) {
                return $baris;
            }
        }
        return collect();
    }
}
