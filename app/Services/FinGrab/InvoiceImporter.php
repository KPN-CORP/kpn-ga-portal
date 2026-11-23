<?php

namespace App\Services\FinGrab;

use Illuminate\Support\Facades\DB;

/**
 * Simpan invoice (PDF) per nomor invoice, lalu bagikan fee dan PPN-nya ke transaksi BCR yang berpasangan.
 * Pasangan dikenali dari angka di nama file: invoice_<angka>_... dan bcr_<angka>_... (angka yang sama).
 * Upload ulang invoice yang sama memperbarui barisnya, tidak menggandakan.
 */
class InvoiceImporter
{
    /**
     * @param  string[]  $paths  path PDF
     * @param  string[]  $names  nama asli file
     * @param  string[]  $extraBcrNos  nomor BCR yang baru diunggah (supaya fee-nya ikut dibagikan walau invoice sudah ada dari upload sebelumnya)
     */
    public function import(array $paths, array $names, array $extraBcrNos = []): array
    {
        $out = ['invoices' => [], 'errors' => [], 'alloc' => []];
        $keys = [];

        foreach ($paths as $i => $path) {
            $name = $names[$i] ?? basename($path);
            if (!preg_match('/^invoice_(\d+)_(\d+)_(.+?)_\d+\.pdf$/i', $name, $m)) {
                $out['errors'][] = "{$name}: nama file tidak sesuai pola invoice_<angka>_<id>_<PT>_<angka>.pdf";
                continue;
            }
            [, $bcrNo, $gfb, $pt] = $m;

            $res = InvoiceParser::parse($path);
            if (!$res['ok']) {
                $out['errors'][] = "{$name}: " . $res['error'];
                continue;
            }
            $d = $res['data'];

            $row = [
                'bcr_no' => $bcrNo, 'gfb_id' => $gfb, 'pt_name' => trim($pt),
                'invoice_date' => $d['invoice_date'], 'due_date' => $d['due_date'],
                'period_from' => $d['period_from'], 'period_to' => $d['period_to'],
                'fee_pct' => $d['fee_pct'], 'reimbursement' => $d['reimbursement'], 'fee' => $d['fee'],
                'tax_base' => $d['tax_base'], 'vat' => $d['vat'],
                'total_excl' => $d['total_excl'], 'total_incl' => $d['total_incl'],
                'file_name' => mb_substr($name, 0, 255), 'updated_at' => now(),
            ];
            $exists = DB::table('fingrab_invoices')->where('invoice_no', $d['invoice_no'])->exists();
            if ($exists) {
                DB::table('fingrab_invoices')->where('invoice_no', $d['invoice_no'])->update($row);
            } else {
                DB::table('fingrab_invoices')->insert($row + ['invoice_no' => $d['invoice_no'], 'created_at' => now()]);
            }

            $keys[$bcrNo] = true;
            $out['invoices'][] = ['invoice_no' => $d['invoice_no'], 'bcr_no' => $bcrNo, 'fee' => $d['fee'], 'vat' => $d['vat'], 'new' => !$exists];
        }

        foreach ($extraBcrNos as $k) {
            $keys[$k] = true;
        }
        foreach (array_keys($keys) as $k) {
            $out['alloc'][$k] = FeeAllocator::allocate((string) $k);
        }

        return $out;
    }
}
