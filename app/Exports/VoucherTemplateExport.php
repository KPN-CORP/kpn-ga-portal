<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Template upload voucher — SATU format saja (tidak ada lagi single/double terpisah).
 * Kolom kode_voucher mendukung 1 kode ("wrtgf") ATAU 2 kode digabung dengan " & "
 * ("kdfjd & jhdfu") langsung di sel yang sama — kalau ada " & ", otomatis dianggap
 * 1 voucher gabungan (nominal di baris itu dianggap total gabungan, bukan per kode).
 */
class VoucherTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function headings(): array
    {
        return ['kode_voucher', 'nominal', 'Status', 'tipe', 'expired_at', 'Business Unit', 'Dibebankan ke BU'];
    }

    public function array(): array
    {
        return [
            // 1 voucher biasa
            ['wrtgf', 50000, 'available', 'grab', '2026-12-31', '', ''],
            // 2 kode digabung jadi 1 voucher (pisahkan dengan " & ", nominal = total gabungan)
            ['kdfjd & jhdfu', 100000, 'available', 'gojek', '', '', ''],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}