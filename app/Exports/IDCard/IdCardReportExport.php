<?php

namespace App\Exports\IDCard;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class IdCardReportExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    public function __construct($query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return ['ID', 'NIK', 'Nama', 'Kategori', 'Status', 'Bisnis Unit', 'Masa Berlaku', 'Sampai Tanggal', 'Nomor Kartu', 'Tanggal Join', 'Keterangan', 'Tanggal Request'];
    }

    public function map($row): array
    {
        $bisnisUnit = DB::table('tb_bisnis_unit')->where('id_bisnis_unit', $row->bisnis_unit_id)->first();
        return [
            $row->id,
            $row->nik,
            $row->nama,
            $row->kategori,
            $row->status,
            $bisnisUnit->nama_bisnis_unit ?? '-',
            $row->masa_berlaku,
            $row->sampai_tanggal,
            $row->nomor_kartu,
            $row->tanggal_join,
            $row->keterangan,
            $row->created_at,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}