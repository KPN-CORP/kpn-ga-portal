<?php

namespace App\Exports;

use App\Models\Work\WorkReport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class WorkReportsExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    protected $month;
    protected $categoryId;
    protected $location;

    public function __construct($month, $categoryId = null, $location = null)
    {
        $this->month = $month;
        $this->categoryId = $categoryId;
        $this->location = $location;
    }

    public function collection()
    {
        $startDate = \Carbon\Carbon::parse($this->month)->startOfMonth();
        $endDate = \Carbon\Carbon::parse($this->month)->endOfMonth();

        $query = WorkReport::with(['category', 'creator'])
            ->whereBetween('report_date', [$startDate, $endDate]);

        if ($this->categoryId) {
            $query->where('category_id', $this->categoryId);
        }
        if ($this->location) {
            $query->where('location', 'LIKE', '%' . $this->location . '%');
        }

        return $query->orderBy('report_date', 'desc')
                     ->orderBy('start_time', 'desc')
                     ->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Kategori',
            'Lantai',
            'Lokasi',
            'Tanggal',
            'Jam Mulai',
            'Jam Selesai',
            'Keterangan',
            'Dibuat Oleh',
            'Foto Sebelum',
            'Foto Sesudah'
        ];
    }

    public function map($report): array
    {
        return [
            $report->id,
            $report->category->name,
            $report->floor,
            $report->location,
            \Carbon\Carbon::parse($report->report_date)->isoFormat('D MMMM Y'),
            \Carbon\Carbon::parse($report->start_time)->format('H:i'),
            \Carbon\Carbon::parse($report->end_time)->format('H:i'),
            $report->description,
            $report->creator->name,
            $report->photo_before ?: '',
            $report->photo_after ?: '',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Set lebar kolom foto (J dan K)
                $sheet->getColumnDimension('J')->setWidth(20);
                $sheet->getColumnDimension('K')->setWidth(20);

                // Tinggi gambar yang diinginkan dalam pixel
                $imageHeight = 80;
                // Konversi pixel ke point (1 pixel ≈ 0.75 point)
                $rowHeight = $imageHeight / 0.75;

                for ($row = 2; $row <= $highestRow; $row++) {
                    $hasImage = false;

                    // Foto Sebelum (kolom J)
                    $beforePath = $sheet->getCell('J' . $row)->getValue();
                    if ($beforePath) {
                        $this->addImageToSheet($sheet, $beforePath, $row, $imageHeight, 'J');
                        $sheet->getCell('J' . $row)->setValue(''); // hapus teks path
                        $hasImage = true;
                    }

                    // Foto Sesudah (kolom K)
                    $afterPath = $sheet->getCell('K' . $row)->getValue();
                    if ($afterPath) {
                        $this->addImageToSheet($sheet, $afterPath, $row, $imageHeight, 'K');
                        $sheet->getCell('K' . $row)->setValue('');
                        $hasImage = true;
                    }

                    if ($hasImage) {
                        $sheet->getRowDimension($row)->setRowHeight($rowHeight);
                    }
                }
            },
        ];
    }

    private function addImageToSheet($sheet, $path, $row, $height, $column)
    {
        $fullPath = storage_path('app/private/' . $path);
        if (!file_exists($fullPath)) return;

        $drawing = new Drawing();
        $drawing->setPath($fullPath);
        $drawing->setHeight($height);
        $drawing->setCoordinates($column . $row);
        $drawing->setOffsetX(2);
        $drawing->setOffsetY(2);
        $drawing->setWorksheet($sheet);

        // Atur lebar secara proporsional (opsional)
        $imageInfo = getimagesize($fullPath);
        if ($imageInfo && $imageInfo[1] > 0) {
            $width = ($imageInfo[0] / $imageInfo[1]) * $height;
            $drawing->setWidth($width);
        }
    }
}