<?php

namespace App\Services\FinGrab;

use Smalot\PdfParser\Parser;

/**
 * Membaca PDF "Sales Invoice" Grab. Yang diambil: nomor dan tanggal invoice, Management Fee (nilai dan PPN-nya),
 * serta total invoice. Reimbursement = Total Value (Excl. VAT) dikurangi fee, jadi baris reimbursement apa pun
 * (Biaya Perjalanan, GrabFood, GrabExpress, dst.) ikut terhitung tanpa perlu dikenali satu per satu.
 *
 * Pembaca PDF: perintah `pdftotext` (poppler-utils, sudah ada di server: /usr/bin/pdftotext). Bila tidak ada dan paket
 * smalot/pdfparser terpasang, paket itu dipakai sebagai cadangan. Lokasi pdftotext bisa diatur lewat config('fingrab.pdftotext').
 */
class InvoiceParser
{
    /** @return array{ok: bool, error?: string, data?: array} */
    public static function parse(string $path): array
    {
        try {
            $text = self::extractText($path);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => 'PDF tidak bisa dibaca: ' . $e->getMessage()];
        }
        $t = trim(preg_replace('/\s+/u', ' ', $text));

        $invNo = self::match('/Invoice Number\s*:?\s*(\d+)/i', $t);
        $totExcl = self::money('/Total Value \(Excl\.?\s*VAT\)\s*IDR\s*(-?[\d,]+(?:\.\d+)?)/i', $t);
        $totIncl = self::money('/Total Value \(Incl\.?\s*VAT\)\s*IDR\s*(-?[\d,]+(?:\.\d+)?)/i', $t);
        if ($invNo === null || $totExcl === null || $totIncl === null) {
            return ['ok' => false, 'error' => 'Format invoice tidak dikenali (nomor invoice atau total tidak ditemukan).'];
        }

        $vat = self::money('/VAT\s*@\s*[\d.]+\s*%\s*IDR\s*(-?[\d,]+(?:\.\d+)?)/i', $t) ?? ($totIncl - $totExcl);
        $taxBase = self::money('/Total Tax Based Value.{0,40}?IDR\s*(-?[\d,]+(?:\.\d+)?)/i', $t);

        $fee = 0;
        $feePct = null;
        $from = $to = null;
        if (preg_match('/Management Fee\s*-\s*([\d.]+)\s*%\s*(\d{4})\/(\d{2})\/(\d{2})\s*-\s*(\d{4})\/(\d{2})\/(\d{2})\s+\d+\s+[\d,]+(?:\.\d+)?\s+([\d,]+(?:\.\d+)?)/i', $t, $m)) {
            $feePct = (float) $m[1];
            $from = "{$m[2]}-{$m[3]}-{$m[4]}";
            $to = "{$m[5]}-{$m[6]}-{$m[7]}";
            $fee = self::toInt($m[8]);
        } elseif (preg_match('/(\d{4})\/(\d{2})\/(\d{2})\s*-\s*(\d{4})\/(\d{2})\/(\d{2})/', $t, $m)) {
            $from = "{$m[1]}-{$m[2]}-{$m[3]}";
            $to = "{$m[4]}-{$m[5]}-{$m[6]}";
        }

        return ['ok' => true, 'data' => [
            'invoice_no' => $invNo,
            'invoice_date' => self::dmy('/Invoice Date\s*:?\s*(\d{2})\/(\d{2})\/(\d{4})/i', $t),
            'due_date' => self::dmy('/Due Date\s*:?\s*(\d{2})\/(\d{2})\/(\d{4})/i', $t),
            'period_from' => $from,
            'period_to' => $to,
            'fee_pct' => $feePct,
            'fee' => $fee,
            'vat' => $vat,
            'tax_base' => $taxBase,
            'total_excl' => $totExcl,
            'total_incl' => $totIncl,
            'reimbursement' => $totExcl - $fee,
        ]];
    }

    private static function extractText(string $path): string
    {
        $bin = self::pdftotextBin();
        if ($bin !== null) {
            $proc = proc_open([$bin, '-layout', '-enc', 'UTF-8', $path, '-'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (is_resource($proc)) {
                $out = stream_get_contents($pipes[1]);
                $err = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $code = proc_close($proc);
                if ($code === 0 && trim((string) $out) !== '') {
                    return (string) $out;
                }
                if (!class_exists(Parser::class)) {
                    throw new \RuntimeException('pdftotext gagal (kode ' . $code . ')' . (trim((string) $err) !== '' ? ': ' . trim((string) $err) : ''));
                }
            }
        }
        if (class_exists(Parser::class)) {
            return (new Parser())->parseFile($path)->getText();
        }
        throw new \RuntimeException('pdftotext tidak ditemukan dan paket smalot/pdfparser tidak terpasang.');
    }

    private static function pdftotextBin(): ?string
    {
        $cands = [];
        if (function_exists('config') && config('fingrab.pdftotext')) {
            $cands[] = (string) config('fingrab.pdftotext');
        }
        array_push($cands, '/usr/bin/pdftotext', '/usr/local/bin/pdftotext');
        foreach ($cands as $c) {
            if (is_file($c) && is_executable($c)) {
                return $c;
            }
        }

        return null;
    }

    private static function match(string $re, string $t): ?string
    {
        return preg_match($re, $t, $m) ? $m[1] : null;
    }

    private static function money(string $re, string $t): ?int
    {
        return preg_match($re, $t, $m) ? self::toInt($m[1]) : null;
    }

    private static function toInt(string $s): int
    {
        return (int) round((float) str_replace(',', '', $s));
    }

    private static function dmy(string $re, string $t): ?string
    {
        return preg_match($re, $t, $m) ? "{$m[3]}-{$m[2]}-{$m[1]}" : null;
    }
}
