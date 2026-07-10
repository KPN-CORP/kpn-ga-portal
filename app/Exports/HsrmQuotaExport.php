<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class HsrmQuotaExport implements WithMultipleSheets
{
    protected $mode;
    protected $areaId;

    public function __construct($mode, $areaId = null)
    {
        $this->mode = $mode;
        $this->areaId = $areaId;
    }

    public function sheets(): array
    {
        return [
            new HsrmQuotaSheet('Certificates', $this->mode, $this->areaId, 'certificate'),
            new HsrmQuotaSheet('Equipments', $this->mode, $this->areaId, 'equipment'),
        ];
    }
}