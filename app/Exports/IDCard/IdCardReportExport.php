<?php

namespace App\Exports\IDCard;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export report ID Card.
 *
 * - Semua sel ditulis sebagai TEKS (StringValueBinder): teks yang diawali "="
 *   tidak dieksekusi sebagai formula Excel, dan NIK / nomor kartu tidak kehilangan
 *   angka nol di depan atau berubah jadi notasi ilmiah.
 * - Nama BU dan nama user dimuat sekali di constructor (tidak ada query per baris).
 * - $query harus berurutan stabil (created_at + id), lihat ReportController.
 */
class IdCardReportExport extends StringValueBinder implements
    FromQuery,
    WithHeadings,
    WithMapping,
    ShouldAutoSize,
    WithStyles,
    WithCustomValueBinder
{
    protected $query;
    protected $bisnisUnits;
    protected $users;

    public function __construct($query)
    {
        $this->query       = $query;
        $this->bisnisUnits = DB::table('tb_bisnis_unit')->pluck('nama_bisnis_unit', 'id_bisnis_unit');
        $this->users       = DB::table('users')->pluck('name', 'id');
    }

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'ID', 'NIK', 'Nama', 'Kategori', 'Status', 'Bisnis Unit', 'Kartu Aktif',
            'Masa Berlaku', 'Sampai Tanggal', 'Nomor Kartu', 'Tanggal Join', 'Keterangan',
            'Tanggal Request', 'Disetujui Oleh', 'Tanggal Disetujui',
            'Ditolak Oleh', 'Tanggal Ditolak', 'Alasan Penolakan',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->nik,
            $row->nama,
            $row->kategori_text,
            $row->status_text,
            $this->bisnisUnits->get($row->bisnis_unit_id, '-'),
            $row->status === 'approved' ? ($row->is_active ? 'Aktif' : 'Nonaktif') : '-',
            $this->tgl($row->masa_berlaku),
            $this->tgl($row->sampai_tanggal),
            $row->nomor_kartu,
            $this->tgl($row->tanggal_join),
            $row->keterangan,
            $this->tgl($row->created_at, true),
            $this->namaUser($row->approved_by),
            $this->tgl($row->approved_at, true),
            $this->namaUser($row->rejected_by),
            $this->tgl($row->rejected_at, true),
            $row->rejection_reason,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    private function tgl($value, bool $withTime = false): string
    {
        return $value ? $value->format($withTime ? 'd-m-Y H:i' : 'd-m-Y') : '';
    }

    private function namaUser($id): string
    {
        // approved_by / rejected_by bertipe varchar di database, berisi id user
        return $id !== null && $id !== '' ? (string) $this->users->get((int) $id, '-') : '';
    }
}
