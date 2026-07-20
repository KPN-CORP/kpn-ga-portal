<?php

namespace App\Exports;

use App\Models\Drms\DriverRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\Auth;

class AdminHistoryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        return [
            'No. Request',
            'Pemohon',
            'Tanggal Penggunaan',
            'Jam Mulai',
            'Jam Selesai',
            'Tipe Perjalanan',
            'Tanggal Kembali',
            'Lokasi Jemput',
            'Tujuan',
            'Keperluan',
            'Jenis Transportasi',
            'Driver',
            'Kendaraan',
            'Kode Voucher',
            'Jenis Voucher',
            'Status',
            'Disetujui Atasan (Tanggal)',
            'Diproses GA (Tanggal)',
            'Alasan Penolakan'
        ];
    }

    public function map($req): array
    {
        $voucher = $req->voucher;

        return [
            $req->request_no,
            $req->requester->name ?? '-',
            $req->usage_date ? $req->usage_date->format('d-m-Y') : '-',
            $req->start_time,
            $req->end_time,
            $req->trip_type === 'round_trip' ? 'Pulang Pergi' : 'Sekali Jalan',
            $req->return_date ? $req->return_date->format('d-m-Y') : '-',
            $req->pickup_location,
            $req->destination,
            $req->purpose,
            $req->transport_type ? ucfirst(str_replace('_', ' ', $req->transport_type)) : '-',
            $req->driver->name ?? '-',
            $req->vehicle->plate_number ?? '-',
            $voucher ? $voucher->code : '-',
            $voucher ? ucfirst($voucher->type) : '-',
            $req->status === 'approved_admin' ? 'Disetujui' : ($req->status === 'rejected_admin' ? 'Ditolak' : 'Selesai'),
            $req->approved_l1_at ? $req->approved_l1_at->format('d-m-Y H:i') : '-',
            $req->approved_admin_at ? $req->approved_admin_at->format('d-m-Y H:i') : '-',
            $req->rejection_reason ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}