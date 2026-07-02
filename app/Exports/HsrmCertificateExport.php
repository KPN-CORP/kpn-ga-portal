<?php

namespace App\Exports;

use App\Models\HSRM\HsrmCertificate;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HsrmCertificateExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $filters;
    protected $isAdmin;
    protected $areaIds;

    public function __construct($filters = [], $isAdmin = true, $areaIds = [])
    {
        $this->filters = $filters;
        $this->isAdmin = $isAdmin;
        $this->areaIds = $areaIds;
    }

    public function query()
    {
        $query = HsrmCertificate::with(['businessUnit', 'area', 'certificateType', 'creator', 'approver']);

        // Filter berdasarkan role
        if (!$this->isAdmin) {
            $query->whereIn('area_id', $this->areaIds);
        }

        // Filter status_verif
        if (!empty($this->filters['status_verif'])) {
            $query->where('status_verif', $this->filters['status_verif']);
        }

        // Filter area (jika admin pilih area tertentu)
        if (!empty($this->filters['area_id']) && $this->isAdmin) {
            $query->where('area_id', $this->filters['area_id']);
        }

        // Filter rentang tanggal expired
        if (!empty($this->filters['expired_from'])) {
            $query->whereDate('expired_date', '>=', $this->filters['expired_from']);
        }
        if (!empty($this->filters['expired_to'])) {
            $query->whereDate('expired_date', '<=', $this->filters['expired_to']);
        }

        return $query->orderBy('expired_date', 'asc');
    }

    public function headings(): array
    {
        return [
            // 'ID',
            'Employee Name',
            'Certificate Number',
            'Certificate Type',
            'Issuing Authority',
            'Expired Date',
            'Verification Status',
            'Ownership Status',
            'Recommendation',
            'Business Unit',
            'Area',
            'Notes',
            'Created By',
            'Approved By',
            'Approved At',
            // 'Attachment Path',
        ];
    }

    public function map($cert): array
    {
        return [
            // $cert->id,
            $cert->employee_name,
            $cert->nik,
            $cert->certificateType->name ?? '-',
            $cert->instansi_pengurusan ?? '-',
            $cert->expired_date ? $cert->expired_date->format('d-m-Y') : '-',
            ucfirst($cert->status_verif),
            $cert->status_kepemilikan ? 'Checked' : 'Unchecked',
            $cert->rekomendasi === true ? 'Recommended' : ($cert->rekomendasi === false ? 'Not Recommended' : '-'),
            $cert->businessUnit->nama_bisnis_unit ?? '-',
            $cert->area->nama_area ?? '-',
            $cert->notes ?? '-',
            $cert->creator->name ?? '-',
            $cert->approver->name ?? '-',
            $cert->approved_at ? $cert->approved_at->format('d-m-Y H:i') : '-',
            // $cert->attachment_path ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}