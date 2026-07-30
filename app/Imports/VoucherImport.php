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
     * @param string|null $defaultExpiredAt Tanggal expired default (Y-m-d), dipakai untuk baris yang
     *                                      tidak mengisi kolom expired_at/expired_at_1/expired_at_2 sendiri.
     */
    public function __construct(
        protected string $format,
        protected $businessUnitId,
        protected $defaultExpiredAt = null
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
                    [$row['kode_voucher_1'] ?? null, $row['nominal_1'] ?? null, $row['tipe_1'] ?? null, $row['expired_at_1'] ?? null],
                    [$row['kode_voucher_2'] ?? null, $row['nominal_2'] ?? null, $row['tipe_2'] ?? null, $row['expired_at_2'] ?? null],
                ]
                : [
                    [$row['kode_voucher'] ?? null, $row['nominal'] ?? null, $row['tipe'] ?? null, $row['expired_at'] ?? null],
                ];

            foreach ($slots as $slot) {
                [$code, $nominal, $type, $expiredRaw] = $slot;
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

                try {
                    $expiredAt = $this->resolveExpiredAt($expiredRaw);
                } catch (\Exception $e) {
                    $this->errors[] = "Baris {$rowNum}: tanggal expired tidak valid untuk kode {$code}, voucher dilewati.";
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
                    'expired_at'             => $expiredAt,
                ]);
                $this->created++;
            }
        }
    }

    /**
     * Tentukan tanggal expired final untuk satu voucher:
     * - Kalau kolom expired_at di baris/slot diisi, pakai itu (mendukung serial tanggal Excel maupun teks).
     * - Kalau kosong, pakai tanggal expired default dari form upload (boleh null juga).
     *
     * @throws \Exception jika nilai expired_at diisi tapi tidak bisa diparse sebagai tanggal.
     */
    private function resolveExpiredAt($raw): ?string
    {
        $raw = is_string($raw) ? trim($raw) : $raw;

        if ($raw === null || $raw === '') {
            return $this->defaultExpiredAt ?: null;
        }

        // Excel kadang mengirim tanggal sebagai serial number (mis. 46000) alih-alih teks.
        if (is_numeric($raw)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw)->format('Y-m-d');
        }

        // Dukung beberapa format teks umum: 2026-12-31, 31-12-2026, 31/12/2026.
        return \Carbon\Carbon::parse($raw)->format('Y-m-d');
    }
}