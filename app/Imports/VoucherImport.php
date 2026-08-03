<?php

namespace App\Imports;

use App\Models\Drms\Voucher;
use App\Models\BisnisUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class VoucherImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private const VALID_TYPES = ['grab', 'gojek', 'taxi'];
    private const VALID_STATUS = ['available', 'used'];

    public int $created = 0;
    public int $skipped = 0;

    /** @var array<int, string> */
    public array $errors = [];

    /** Cache nama BU -> id, supaya tidak query berulang tiap baris. */
    private ?Collection $businessUnitsByName = null;

    /**
     * @param int|null $defaultBusinessUnitId BU default/fallback (dipilih di form upload),
     *                                         dipakai kalau kolom "Business Unit" di baris kosong.
     * @param string|null $defaultExpiredAt Tanggal expired default (Y-m-d), dipakai kalau kolom
     *                                      expired_at di baris kosong.
     * @param bool $isSuperAdmin Superadmin boleh override Business Unit per baris lewat kolom
     *                           "Business Unit". Non-superadmin SELALU pakai BU mereka sendiri
     *                           (kolom "Business Unit" di file diabaikan, demi keamanan akses).
     * @param bool $isSpecialBu User dari BU khusus (KPN Corporation) — kolom "Dibebankan ke BU"
     *                          cuma berlaku untuk user ini, selain itu selalu diabaikan/null.
     */
    public function __construct(
        protected $defaultBusinessUnitId,
        protected $defaultExpiredAt = null,
        protected bool $isSuperAdmin = false,
        protected bool $isSpecialBu = false
    ) {
    }

    public function collection(Collection $rows): void
    {
        // WithHeadingRow sudah membuang baris header, jadi baris data pertama = baris ke-2 di file.
        $rowNum = 1;

        foreach ($rows as $row) {
            $rowNum++;
            $this->processRow($row, $rowNum);
        }
    }

    private function processRow($row, int $rowNum): void
    {
        $rawCode = trim((string) ($row['kode_voucher'] ?? ''));
        if ($rawCode === '') {
            return; // baris kosong, dilewati tanpa dihitung error
        }

        // Kode boleh 1 ("wrtgf") atau digabung dengan " & " ("kdfjd & jhdfu") — kalau digabung,
        // dianggap 1 voucher gabungan; nominal di baris itu adalah TOTAL gabungan, bukan per kode.
        $code = $rawCode;
        if (str_contains($code, '&')) {
            $parts = array_values(array_filter(array_map('trim', explode('&', $code)), fn($p) => $p !== ''));
            if (count($parts) < 2) {
                $this->errors[] = "Baris {$rowNum}: format kode gabungan '{$rawCode}' tidak valid (butuh 2 kode dipisah '&').";
                $this->skipped++;
                return;
            }
            $code = implode(' & ', $parts);
        }

        $type = strtolower(trim((string) ($row['tipe'] ?? '')));
        if ($type === 'bluebird') {
            $type = 'taxi';
        }
        if (!in_array($type, self::VALID_TYPES, true)) {
            $this->errors[] = "Baris {$rowNum}: tipe '{$type}' tidak valid untuk kode {$code} (harus grab/gojek/taxi).";
            $this->skipped++;
            return;
        }

        $nominal = $row['nominal'] ?? null;
        if (!is_numeric($nominal) || (float) $nominal < 0) {
            $this->errors[] = "Baris {$rowNum}: nominal tidak valid untuk kode {$code}.";
            $this->skipped++;
            return;
        }

        $status = strtolower(trim((string) ($row['status'] ?? '')));
        if ($status === '') {
            $status = 'available'; // default kalau kolom Status dikosongkan
        }
        if (!in_array($status, self::VALID_STATUS, true)) {
            $this->errors[] = "Baris {$rowNum}: status '{$status}' tidak valid untuk kode {$code} (harus available/used).";
            $this->skipped++;
            return;
        }

        if (Voucher::where('code', $code)->exists()) {
            $this->errors[] = "Baris {$rowNum}: kode {$code} sudah ada, dilewati.";
            $this->skipped++;
            return;
        }

        try {
            $expiredAt = $this->resolveExpiredAt($row['expired_at'] ?? null);
        } catch (\Exception $e) {
            $this->errors[] = "Baris {$rowNum}: tanggal expired tidak valid untuk kode {$code}, voucher dilewati.";
            $this->skipped++;
            return;
        }

        // Business Unit: hanya superadmin yang boleh override per baris lewat kolom "Business Unit".
        // Non-superadmin selalu pakai BU mereka sendiri (defaultBusinessUnitId), kolom di file diabaikan.
        $businessUnitId = $this->defaultBusinessUnitId;
        if ($this->isSuperAdmin) {
            $buName = trim((string) ($row['business_unit'] ?? ''));
            if ($buName !== '') {
                $found = $this->findBusinessUnitByName($buName);
                if (!$found) {
                    $this->errors[] = "Baris {$rowNum}: Business Unit '{$buName}' tidak ditemukan untuk kode {$code}, voucher dilewati.";
                    $this->skipped++;
                    return;
                }
                $businessUnitId = $found;
            }
        }
        if (!$businessUnitId) {
            $this->errors[] = "Baris {$rowNum}: Business Unit belum ditentukan untuk kode {$code} (isi kolom Business Unit, atau pilih BU default di form upload).";
            $this->skipped++;
            return;
        }

        // Dibebankan ke BU (input_business_unit_id): cuma berlaku untuk user BU khusus (KPN Corporation).
        $inputBusinessUnitId = null;
        if ($this->isSpecialBu) {
            $dibebankanName = trim((string) ($row['dibebankan_ke_bu'] ?? ''));
            if ($dibebankanName !== '') {
                $found = $this->findBusinessUnitByName($dibebankanName);
                if (!$found) {
                    $this->errors[] = "Baris {$rowNum}: Business Unit tujuan (Dibebankan ke BU) '{$dibebankanName}' tidak ditemukan untuk kode {$code}, voucher dilewati.";
                    $this->skipped++;
                    return;
                }
                $inputBusinessUnitId = $found;
            }
        }

        Voucher::create([
            'code'                   => $code,
            'nominal'                => (float) $nominal,
            'type'                   => $type,
            'status'                 => $status,
            'business_unit_id'       => $businessUnitId,
            'input_business_unit_id' => $inputBusinessUnitId,
            'expired_at'             => $expiredAt,
        ]);
        $this->created++;
    }

    /**
     * Cari id_bisnis_unit dari nama (case-insensitive), pakai cache supaya query
     * daftar BU cuma dijalankan sekali per proses upload, bukan per baris.
     */
    private function findBusinessUnitByName(string $name): ?int
    {
        if ($this->businessUnitsByName === null) {
            $this->businessUnitsByName = BisnisUnit::pluck('id_bisnis_unit', 'nama_bisnis_unit')
                ->mapWithKeys(fn ($id, $nama) => [strtolower(trim($nama)) => $id]);
        }
        return $this->businessUnitsByName->get(strtolower(trim($name)));
    }

    /**
     * Tentukan tanggal expired final untuk satu voucher:
     * - Kalau kolom expired_at di baris diisi, pakai itu (mendukung serial tanggal Excel maupun teks).
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