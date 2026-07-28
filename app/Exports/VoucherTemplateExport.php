<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VoucherTemplateExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    /**
     * @param string $type 'single' (1 voucher/baris) atau 'double' (2 voucher/baris)
     */
    public function __construct(protected string $type = 'single')
    {
    }

    public function headings(): array
    {
        return $this->type === 'double'
            ? ['kode_voucher_1', 'nominal_1', 'tipe_1', 'kode_voucher_2', 'nominal_2', 'tipe_2']
            : ['kode_voucher', 'nominal', 'tipe'];
    }

    public function array(): array
    {
        if ($this->type === 'double') {
            return [
                ['GRB1000001', 50000, 'grab', 'GJK1000001', 30000, 'gojek'],
                ['TXI1000002', 75000, 'taxi', '', '', ''],
            ];
        }

        return [
            ['GRB1000001', 50000, 'grab'],
            ['GJK1000001', 30000, 'gojek'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
