<?php

namespace App\Exports;

use App\Models\BisnisUnit;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Facades\DB;

class AdminHistoryExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $query;

    /** Cache id_bisnis_unit => nama_bisnis_unit (diisi sekali) */
    protected ?array $businessUnitNames = null;

    /** Cache unit HCIS per pemohon agar tidak query berulang */
    protected array $hcisUnitCache = [];

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
            // Identitas request
            'No. Request',
            'Status',
            'Pemohon',
            'Bisnis Unit',
            'Unit (HCIS)',
            // Waktu penggunaan
            'Tanggal Penggunaan',
            'Jam Mulai',
            'Jam Selesai',
            'Tanggal Kembali',
            // Perjalanan
            'Tipe Perjalanan',
            'Jenis Dinas',
            'Lokasi Jemput',
            'Tujuan',
            'Keperluan',
            // Transportasi
            'Jenis Transportasi',
            'Driver',
            'Kendaraan',
            'Voucher',
            'BU Voucher',
            'Beban BU Voucher',
            // Approval
            'Approver L1',
            'Disetujui Atasan (Tanggal)',
            'Admin',
            'Diproses GA (Tanggal)',
            'Catatan Admin',
        ];
    }

    public function map($req): array
    {
        $distanceTypeLabel = match ($req->distance_type) {
            'jarak_dekat' => 'Dinas Jarak Dekat',
            'jarak_jauh'  => 'Dinas Jarak Jauh',
            default       => '-',
        };

        // Bisa lebih dari 1 voucher per request, digabung dengan titik koma
        // dalam 1 kolom: "KODE1 (tipe, Rp x); KODE2 (tipe, Rp y)"
        $voucherLabel = $req->vouchers->isNotEmpty()
            ? $req->vouchers->map(function ($v) {
                return $v->code . ' (' . ucfirst($v->type) . ', Rp ' . number_format($v->nominal, 0, ',', '.') . ')';
            })->implode('; ')
            : '-';

        $statusLabel = $req->status === 'approved_admin'
            ? 'Disetujui'
            : ($req->status === 'rejected_admin' ? 'Ditolak' : 'Selesai');

        return [
            $req->request_no,
            $statusLabel,
            $req->requester->name ?? '-',
            $this->businessUnitName($req),
            $this->hcisUnit($req->requester),
            $req->usage_date ? $req->usage_date->format('d-m-Y') : '-',
            $req->start_time,
            $req->end_time,
            $req->return_date ? $req->return_date->format('d-m-Y') : '-',
            $req->trip_type === 'round_trip' ? 'Pulang Pergi' : 'Sekali Jalan',
            $distanceTypeLabel,
            $req->pickup_location,
            $req->destination,
            $req->purpose,
            $req->transport_type ? ucfirst(str_replace('_', ' ', $req->transport_type)) : '-',
            $req->driver->name ?? '-',
            $req->vehicle->plate_number ?? '-',
            $voucherLabel,
            $this->voucherBuLabel($req, 'business_unit_id'),
            $this->voucherBuLabel($req, 'input_business_unit_id'),
            $req->approverL1->name ?? '-',
            $req->approved_l1_at ? $req->approved_l1_at->format('d-m-Y H:i') : '-',
            $req->admin->name ?? '-',
            $req->approved_admin_at ? $req->approved_admin_at->format('d-m-Y H:i') : '-',
            $req->rejection_reason ?? '-',
        ];
    }

    /**
     * Nama Bisnis Unit: pakai current_business_unit_id (bisa berubah jika request
     * di-forward), fallback ke bisnis unit dari profil DRMS pemohon.
     */
    protected function businessUnitName($req): string
    {
        $this->loadBusinessUnitNames();

        $buId = $req->current_business_unit_id
            ?? $req->requester->drmsProfile->business_unit_id
            ?? null;

        return $buId ? ($this->businessUnitNames[$buId] ?? '-') : '-';
    }

    protected function loadBusinessUnitNames(): void
    {
        if ($this->businessUnitNames === null) {
            $this->businessUnitNames = BisnisUnit::pluck('nama_bisnis_unit', 'id_bisnis_unit')->all();
        }
    }

    /**
     * Nama BU per voucher, urutannya sama dengan kolom Voucher.
     * $column: 'business_unit_id' (BU voucher) atau 'input_business_unit_id' (Dibebankan ke BU).
     * Kalau request punya lebih dari 1 voucher, tiap item diberi prefix kode voucher.
     */
    protected function voucherBuLabel($req, string $column): string
    {
        if ($req->vouchers->isEmpty()) {
            return '-';
        }

        $this->loadBusinessUnitNames();
        $multiple = $req->vouchers->count() > 1;

        return $req->vouchers->map(function ($v) use ($column, $multiple) {
            $name = $v->{$column} ? ($this->businessUnitNames[$v->{$column}] ?? '-') : '-';
            return $multiple ? $v->code . ': ' . $name : $name;
        })->implode('; ');
    }

    /**
     * Unit dari tabel api_emp_hcis. Dicocokkan lewat employee_no = employee_id,
     * fallback ke email.
     */
    protected function hcisUnit($requester): string
    {
        if (!$requester) {
            return '-';
        }

        $key = $requester->id;
        if (array_key_exists($key, $this->hcisUnitCache)) {
            return $this->hcisUnitCache[$key];
        }

        $unit = null;

        if (!empty($requester->employee_no)) {
            $unit = DB::table('api_emp_hcis')
                ->where('employee_id', $requester->employee_no)
                ->value('unit');
        }

        if ($unit === null && !empty($requester->email)) {
            $unit = DB::table('api_emp_hcis')
                ->where('email', $requester->email)
                ->value('unit');
        }

        return $this->hcisUnitCache[$key] = ($unit ?: '-');
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}