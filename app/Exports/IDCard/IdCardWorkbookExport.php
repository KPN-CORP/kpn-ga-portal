<?php

namespace App\Exports\IDCard;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Satu file Excel berisi beberapa sheet report ID Card. */
class IdCardWorkbookExport implements WithMultipleSheets
{
    protected array $sheets;

    public function __construct(array $sheets)
    {
        $this->sheets = $sheets;
    }

    public function sheets(): array
    {
        return $this->sheets;
    }
}
