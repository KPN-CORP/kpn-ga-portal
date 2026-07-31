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
     * @param string $format 'single' (1 voucher/baris) atau 'double' (2 kode digabung jadi 1 voucher/baris)
     * @param int|null $businessUnitId Business unit yang otomatis dipakai untuk semua voucher yang diupload
     * @param string|null $defaultExpiredAt Tanggal expired default (Y-m-d), dipakai untuk baris yang
     *                                      tidak mengisi kolom expired_at sendiri.
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

            if ($this->format === 'double') {
                $this->processDoubleRow($row, $rowNum);
            } else {
                $this->processSingleSlot(
                    $row['kode_voucher'] ?? null,
                    $row['nominal'] ?? null,
                    $row['tipe'] ?? null,
                    $row['expired_at'] ?? null,
                    $rowNum
                );
            }
        }
    }

    /**
     * Template "2 voucher per baris": kode_voucher_1 & kode_voucher_2 DIGABUNG jadi
     * SATU voucher (code: "kode1 & kode2", nominal dijumlah, tipe wajib sama).
     * Kalau cuma salah satu slot yang diisi (baris ganjil), diperlakukan sebagai
     * voucher tunggal biasa (tidak digabung, karena memang cuma 1 kode).
     */
    private function processDoubleRow($row, int $rowNum): void
    {
        $code1 = trim((string) ($row['kode_voucher_1'] ?? ''));
        $code2 = trim((string) ($row['kode_voucher_2'] ?? ''));

        if ($code1 === '' && $code2 === '') {
            return; // baris kosong total, dilewati tanpa dihitung error
        }

        // Cuma salah satu slot yang diisi -> perlakukan sebagai voucher tunggal (tidak digabung).
        if ($code2 === '') {
            $this->processSingleSlot($code1, $row['nominal_1'] ?? null, $row['tipe_1'] ?? null, $row['expired_at_1'] ?? null, $rowNum);
            return;
        }
        if ($code1 === '') {
            $this->processSingleSlot($code2, $row['nominal_2'] ?? null, $row['tipe_2'] ?? null, $row['expired_at_2'] ?? null, $rowNum);
            return;
        }

        // Dua-duanya diisi -> gabung jadi 1 voucher.
        $type1 = strtolower(trim((string) ($row['tipe_1'] ?? '')));
        $type2 = strtolower(trim((string) ($row['tipe_2'] ?? '')));
        if ($type1 === 'bluebird') $type1 = 'taxi';
        if ($type2 === 'bluebird') $type2 = 'taxi';

        if (!in_array($type1, self::VALID_TYPES, true) || !in_array($type2, self::VALID_TYPES, true)) {
            $this->errors[] = "Baris {$rowNum}: tipe tidak valid untuk kode {$code1} & {$code2} (harus grab/gojek/taxi).";
            $this->skipped++;
            return;
        }

        if ($type1 !== $type2) {
            $this->errors[] = "Baris {$rowNum}: tipe_1 ({$type1}) dan tipe_2 ({$type2}) berbeda untuk kode {$code1} & {$code2} — tipe harus sama supaya bisa digabung, baris dilewati.";
            $this->skipped++;
            return;
        }

        $nominal1 = $row['nominal_1'] ?? null;
        $nominal2 = $row['nominal_2'] ?? null;
        if (!is_numeric($nominal1) || (float) $nominal1 < 0 || !is_numeric($nominal2) || (float) $nominal2 < 0) {
            $this->errors[] = "Baris {$rowNum}: nominal tidak valid untuk kode {$code1} & {$code2}.";
            $this->skipped++;
            return;
        }

        $combinedCode = $code1 . ' & ' . $code2;

        if (Voucher::where('code', $combinedCode)->exists()) {
            $this->errors[] = "Baris {$rowNum}: kode {$combinedCode} sudah ada, dilewati.";
            $this->skipped++;
            return;
        }

        // Tanggal expired: pakai expired_at_1 kalau diisi, kalau kosong pakai expired_at_2,
        // kalau dua-duanya kosong pakai tanggal default dari form upload (boleh null juga).
        $expiredRaw = $row['expired_at_1'] ?? null;
        if ($expiredRaw === null || trim((string) $expiredRaw) === '') {
            $expiredRaw = $row['expired_at_2'] ?? null;
        }

        try {
            $expiredAt = $this->resolveExpiredAt($expiredRaw);
        } catch (\Exception $e) {
            $this->errors[] = "Baris {$rowNum}: tanggal expired tidak valid untuk kode {$combinedCode}, voucher dilewati.";
            $this->skipped++;
            return;
        }

        Voucher::create([
            'code'                   => $combinedCode,
            'nominal'                => (float) $nominal1 + (float) $nominal2,
            'type'                   => $type1,
            'status'                 => 'available',
            'business_unit_id'       => $this->businessUnitId,
            'input_business_unit_id' => null,
            'expired_at'             => $expiredAt,
        ]);
        $this->created++;
    }

    /**
     * Simpan 1 voucher tunggal (dipakai untuk template "single", dan untuk baris
     * ganjil di template "double" yang cuma punya 1 kode terisi).
     */
    private function processSingleSlot($code, $nominal, $type, $expiredRaw, int $rowNum): void
    {
        $code = trim((string) $code);
        if ($code === '') {
            return;
        }

        $type = strtolower(trim((string) $type));
        if ($type === 'bluebird') {
            $type = 'taxi';
        }

        if (!in_array($type, self::VALID_TYPES, true)) {
            $this->errors[] = "Baris {$rowNum}: tipe '{$type}' tidak valid untuk kode {$code} (harus grab/gojek/taxi).";
            $this->skipped++;
            return;
        }

        if (!is_numeric($nominal) || (float) $nominal < 0) {
            $this->errors[] = "Baris {$rowNum}: nominal tidak valid untuk kode {$code}.";
            $this->skipped++;
            return;
        }

        if (Voucher::where('code', $code)->exists()) {
            $this->errors[] = "Baris {$rowNum}: kode {$code} sudah ada, dilewati.";
            $this->skipped++;
            return;
        }

        try {
            $expiredAt = $this->resolveExpiredAt($expiredRaw);
        } catch (\Exception $e) {
            $this->errors[] = "Baris {$rowNum}: tanggal expired tidak valid untuk kode {$code}, voucher dilewati.";
            $this->skipped++;
            return;
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
