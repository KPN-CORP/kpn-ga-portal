<?php

namespace App\Imports;

use App\Models\Drms\Voucher;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class VoucherImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private const VALID_TYPES = ['grab', 'gojek', 'taxi'];

    public int $created = 0;
    public int $skipped = 0;

    /** @var array<int, string> */
    public array $errors = [];

    /**
     * @param string $format 'single' (1 voucher/baris) atau 'double' (2 voucher/baris)
     * @param int|null $businessUnitId Business unit yang otomatis dipakai untuk semua voucher yang diupload
     */
    public function __construct(
        protected string $format,
        protected $businessUnitId
    ) {
    }

    public function collection(Collection $rows): void
    {
        // WithHeadingRow sudah membuang baris header, jadi baris data pertama = baris ke-2 di file.
        $rowNum = 1;

        foreach ($rows as $row) {
            $rowNum++;

            $slots = $this->format === 'double'
                ? [
                    [$row['kode_voucher_1'] ?? null, $row['nominal_1'] ?? null, $row['tipe_1'] ?? null],
                    [$row['kode_voucher_2'] ?? null, $row['nominal_2'] ?? null, $row['tipe_2'] ?? null],
                ]
                : [
                    [$row['kode_voucher'] ?? null, $row['nominal'] ?? null, $row['tipe'] ?? null],
                ];

            foreach ($slots as $slot) {
                [$code, $nominal, $type] = $slot;
                $code = trim((string) $code);

                // Slot kosong (khusus baris ganjil pada template 2 voucher/baris) dilewati tanpa dihitung error.
                if ($code === '') {
                    continue;
                }

                $type = strtolower(trim((string) $type));
                if ($type === 'bluebird') {
                    $type = 'taxi';
                }

                if (!in_array($type, self::VALID_TYPES, true)) {
                    $this->errors[] = "Baris {$rowNum}: tipe '{$type}' tidak valid untuk kode {$code} (harus grab/gojek/taxi).";
                    $this->skipped++;
                    continue;
                }

                if (!is_numeric($nominal) || (float) $nominal < 0) {
                    $this->errors[] = "Baris {$rowNum}: nominal tidak valid untuk kode {$code}.";
                    $this->skipped++;
                    continue;
                }

                if (Voucher::where('code', $code)->exists()) {
                    $this->errors[] = "Baris {$rowNum}: kode {$code} sudah ada, dilewati.";
                    $this->skipped++;
                    continue;
                }

                Voucher::create([
                    'code'                   => $code,
                    'nominal'                => (float) $nominal,
                    'type'                   => $type,
                    'status'                 => 'available',
                    'business_unit_id'       => $this->businessUnitId,
                    'input_business_unit_id' => null,
                ]);
                $this->created++;
            }
        }
    }
}
