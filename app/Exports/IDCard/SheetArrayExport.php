<?php

namespace App\Exports\IDCard;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet generik (judul + heading + baris array) untuk sheet tambahan di report ID Card
 * (Stok Saat Ini, Mutasi Stok, Kartu Pinjam). Semua sel ditulis sebagai teks, sama seperti IdCardReportExport.
 */
class SheetArrayExport extends StringValueBinder implements
    FromArray,
    WithHeadings,
    WithTitle,
    ShouldAutoSize,
    WithStyles,
    WithCustomValueBinder
{
    protected string $judul;
    protected array $headings;
    protected array $rows;

    public function __construct(string $judul, array $headings, array $rows)
    {
        $this->judul    = $judul;
        $this->headings = $headings;
        $this->rows     = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function title(): string
    {
        return $this->judul;
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
