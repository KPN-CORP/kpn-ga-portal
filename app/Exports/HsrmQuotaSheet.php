<?php

namespace App\Exports;

use App\Models\AreaKerja;
use App\Models\HSRM\HsrmCertificateQuota;
use App\Models\HSRM\HsrmEquipmentQuota;
use App\Models\HSRM\HsrmCertificate;
use App\Models\HSRM\HsrmEquipment;
use App\Models\HSRM\HsrmCertificateType;
use App\Models\HSRM\HsrmEquipmentType;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HsrmQuotaSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $title;
    protected $mode;
    protected $areaId;
    protected $type;

    public function __construct($title, $mode, $areaId, $type)
    {
        $this->title = $title;
        $this->mode = $mode;
        $this->areaId = $areaId;
        $this->type = $type;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function headings(): array
    {
        if ($this->type === 'certificate') {
            return [
                'Area',
                'Certificate Type',
                'Regulatory',
                'Quota',
                'Active',
                'Expired',
                'Status',
                'Budget (Rp)',
                'Action',
                'Created At',
                'Updated At',
            ];
        } else {
            return [
                'Area',
                'Equipment Type',
                'Quota (items)',
                'Active (items)',
                'Expired (items)',
                'Status',
                'Budget (Rp)',
                'Action',
                'Created At',
                'Updated At',
            ];
        }
    }

    public function array(): array
    {
        $data = [];

        if ($this->mode === 'single') {
            $area = AreaKerja::find($this->areaId);
            if ($area) {
                $data = $this->getDataForArea($area);
            }
        } else {
            $areas = $this->getAreasWithData();
            foreach ($areas as $area) {
                $areaData = $this->getDataForArea($area);
                $data = array_merge($data, $areaData);
            }
        }

        if (empty($data)) {
            if ($this->type === 'certificate') {
                $data[] = ['Tidak ada data sertifikat', '', '', '', '', '', '', '', '', '', ''];
            } else {
                $data[] = ['Tidak ada data peralatan', '', '', '', '', '', '', '', '', ''];
            }
        }

        return $data;
    }

    private function getAreasWithData()
    {
        $areaIds = collect();
        if ($this->type === 'certificate') {
            $certAreas = HsrmCertificate::select('area_id')->distinct()->pluck('area_id');
            $quotaCertAreas = HsrmCertificateQuota::select('area_id')->distinct()->pluck('area_id');
            $areaIds = $certAreas->merge($quotaCertAreas)->unique();
        } else {
            $eqAreas = HsrmEquipment::select('area_id')->distinct()->pluck('area_id');
            $quotaEqAreas = HsrmEquipmentQuota::select('area_id')->distinct()->pluck('area_id');
            $areaIds = $eqAreas->merge($quotaEqAreas)->unique();
        }
        return AreaKerja::whereIn('id_area_kerja', $areaIds)->orderBy('nama_area')->get();
    }

    private function getDataForArea($area)
    {
        $data = [];

        if ($this->type === 'certificate') {
            $types = HsrmCertificateType::orderBy('name')->get();
            foreach ($types as $type) {
                $quota = HsrmCertificateQuota::where('area_id', $area->id_area_kerja)
                            ->where('certificate_type_id', $type->id)
                            ->first();

                $active = HsrmCertificate::where('area_id', $area->id_area_kerja)
                            ->where('certificate_type_id', $type->id)
                            ->where('status_verif', 'verified')
                            ->where('expired_date', '>', now())
                            ->count();

                $expired = HsrmCertificate::where('area_id', $area->id_area_kerja)
                            ->where('certificate_type_id', $type->id)
                            ->where('expired_date', '<=', now())
                            ->count();

                $quotaVal = $quota ? $quota->quota : 0;
                $budgetVal = $quota ? $quota->budget : 0;
                $regulatory = $quota ? $quota->regulatory : '-';
                $appType = $quota ? $quota->application_type : '-';
                $createdAt = $quota ? ($quota->created_at ? $quota->created_at->format('d-m-Y H:i') : '-') : '-';
                $updatedAt = $quota ? ($quota->updated_at ? $quota->updated_at->format('d-m-Y H:i') : '-') : '-';

                $diff = $active - $quotaVal;
                if ($diff < 0) {
                    $status = 'Short ' . abs($diff);
                } elseif ($diff == 0) {
                    $status = 'Sufficient';
                } else {
                    $status = 'Over ' . $diff;
                }
                if ($expired > 0) {
                    $status .= ' (Expired: ' . $expired . ')';
                }

                $data[] = [
                    $area->nama_area,
                    $type->name,
                    $regulatory,
                    $quotaVal,
                    $active,
                    $expired,
                    $status,
                    $budgetVal,
                    $appType,
                    $createdAt,
                    $updatedAt,
                ];
            }
        } else {
            $types = HsrmEquipmentType::orderBy('name')->get();
            foreach ($types as $type) {
                $quota = HsrmEquipmentQuota::where('area_id', $area->id_area_kerja)
                            ->where('equipment_type_id', $type->id)
                            ->first();

                $active = HsrmEquipment::where('area_id', $area->id_area_kerja)
                            ->where('equipment_type_id', $type->id)
                            ->where('status_verif', 'verified')
                            ->where('expired_date', '>', now())
                            ->sum('total_items');

                $expired = HsrmEquipment::where('area_id', $area->id_area_kerja)
                            ->where('equipment_type_id', $type->id)
                            ->where('expired_date', '<=', now())
                            ->sum('total_items');

                $quotaVal = $quota ? $quota->quota : 0;
                $budgetVal = $quota ? $quota->budget : 0;
                $appType = $quota ? $quota->application_type : '-';
                $createdAt = $quota ? ($quota->created_at ? $quota->created_at->format('d-m-Y H:i') : '-') : '-';
                $updatedAt = $quota ? ($quota->updated_at ? $quota->updated_at->format('d-m-Y H:i') : '-') : '-';

                $diff = $active - $quotaVal;
                if ($diff < 0) {
                    $status = 'Short ' . abs($diff);
                } elseif ($diff == 0) {
                    $status = 'Sufficient';
                } else {
                    $status = 'Over ' . $diff;
                }
                if ($expired > 0) {
                    $status .= ' (Expired: ' . $expired . ')';
                }

                $data[] = [
                    $area->nama_area,
                    $type->name,
                    $quotaVal,
                    $active,
                    $expired,
                    $status,
                    $budgetVal,
                    $appType,
                    $createdAt,
                    $updatedAt,
                ];
            }
        }

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        foreach (range('A', $highestColumn) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        if ($highestRow > 1) {
            $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        return [];
    }
}