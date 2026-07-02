<?php

namespace App\Exports;

use App\Models\HSRM\HsrmEquipment;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HsrmEquipmentExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
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
        $query = HsrmEquipment::with(['businessUnit', 'area', 'equipmentType', 'creator', 'approver']);

        if (!$this->isAdmin) {
            $query->whereIn('area_id', $this->areaIds);
        }

        if (!empty($this->filters['status_verif'])) {
            $query->where('status_verif', $this->filters['status_verif']);
        }

        if (!empty($this->filters['area_id']) && $this->isAdmin) {
            $query->where('area_id', $this->filters['area_id']);
        }

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
            'Name',
            'Type',
            'Capacity',
            'Location',
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
            // 'Photo Path',
        ];
    }

    public function map($eq): array
    {
        return [
            // $eq->id,
            $eq->name,
            $eq->equipmentType->name ?? '-',
            $eq->capacity,
            $eq->location ?? '-',
            $eq->expired_date ? $eq->expired_date->format('d-m-Y') : '-',
            ucfirst($eq->status_verif),
            $eq->status_kepemilikan ? 'Checked' : 'Unchecked',
            $eq->rekomendasi === true ? 'Recommended' : ($eq->rekomendasi === false ? 'Not Recommended' : '-'),
            $eq->businessUnit->nama_bisnis_unit ?? '-',
            $eq->area->nama_area ?? '-',
            $eq->notes ?? '-',
            $eq->creator->name ?? '-',
            $eq->approver->name ?? '-',
            $eq->approved_at ? $eq->approved_at->format('d-m-Y H:i') : '-',
            // $eq->photo_path ?? '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}