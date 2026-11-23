<?php

namespace App\Services\FinGrab;

use Illuminate\Support\Facades\DB;

/**
 * Membagi Management Fee dan PPN satu invoice ke semua transaksi dari BCR yang sama (kolom bcr_no).
 *
 * Pembagian sebanding dengan nominal tiap baris, dengan metode largest remainder: sisa pembulatan dibagikan
 * ke baris dengan sisa pecahan terbesar, sehingga jumlah semua baris PERSIS sama dengan angka di invoice
 * (tidak ada selisih Rp 1 seperti kalau tiap transaksi dikali 5% lalu dibulatkan sendiri-sendiri).
 * Aman dijalankan berulang: hanya baris yang nilainya berubah yang diperbarui.
 */
class FeeAllocator
{
    /** @return array{status: string, rows: int, fee: int, vat: int, updated: int} */
    public static function allocate(string $bcrNo): array
    {
        $inv = DB::table('fingrab_invoices')->where('bcr_no', $bcrNo)->first();
        if (!$inv) {
            return ['status' => 'no_invoice', 'rows' => 0, 'fee' => 0, 'vat' => 0, 'updated' => 0];
        }

        $rows = DB::table('fingrab_transactions')->where('bcr_no', $bcrNo)->orderBy('id')
            ->get(['id', 'amount', 'fee_amount', 'vat_amount', 'invoice_id']);
        $weights = [];
        foreach ($rows as $r) {
            $weights[$r->id] = (int) $r->amount;
        }
        $sum = array_sum($weights);
        if (!$rows->count() || $sum <= 0) {
            return ['status' => 'no_rows', 'rows' => $rows->count(), 'fee' => 0, 'vat' => 0, 'updated' => 0];
        }

        $fee = self::split((int) $inv->fee, $weights, $sum);
        $vat = self::split((int) $inv->vat, $weights, $sum);

        $updated = 0;
        DB::transaction(function () use ($rows, $fee, $vat, $inv, &$updated) {
            foreach ($rows as $r) {
                if ((int) $r->fee_amount !== $fee[$r->id] || (int) $r->vat_amount !== $vat[$r->id] || (int) $r->invoice_id !== (int) $inv->id) {
                    DB::table('fingrab_transactions')->where('id', $r->id)->update([
                        'fee_amount' => $fee[$r->id], 'vat_amount' => $vat[$r->id], 'invoice_id' => $inv->id,
                    ]);
                    $updated++;
                }
            }
        });

        return ['status' => 'ok', 'rows' => $rows->count(), 'fee' => array_sum($fee), 'vat' => array_sum($vat), 'updated' => $updated];
    }

    /**
     * @param  array<int,int>  $w  id => bobot (nominal baris)
     * @return array<int,int>      id => bagian dari $total, jumlahnya persis $total
     */
    public static function split(int $total, array $w, int $sumW): array
    {
        $q = [];
        $rem = [];
        $taken = 0;
        foreach ($w as $k => $wi) {
            $num = $total * $wi;
            $qi = intdiv($num, $sumW);
            $ri = $num - $qi * $sumW;
            if ($ri < 0) { // bobot negatif (refund): bulatkan ke bawah
                $qi--;
                $ri += $sumW;
            }
            $q[$k] = $qi;
            $rem[$k] = $ri;
            $taken += $qi;
        }

        $left = $total - $taken;
        if ($left > 0) {
            $keys = array_keys($rem);
            usort($keys, fn ($a, $b) => ($rem[$b] <=> $rem[$a]) ?: ($a <=> $b));
            for ($i = 0; $i < $left; $i++) {
                $q[$keys[$i]]++;
            }
        }

        return $q;
    }
}
