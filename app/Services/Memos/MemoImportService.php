<?php

namespace App\Services\Memos;

use App\Models\Memos\MemoNumberSetting;
use App\Models\Memos\Memos;
use App\Models\Memos\MemosItems;
use App\Models\User;
use App\Support\Memos\MemoImportProfiles;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import file Excel mailing (pengganti mail merge):
 * 1 sheet = 1 jenis surat, 1 baris = 1 memo berstatus DRAFT.
 * Nomor memo baru digenerate saat draft di-submit.
 */
class MemoImportService
{
    /** Masa berlaku draft hasil import (lebih panjang dari draft manual yang 24 jam). */
    private const DRAFT_TTL_DAYS = 7;

    /**
     * @return array{created:int, skipped:string[], needs_review:int}
     * @throws \Throwable kalau file tidak bisa dibaca / gagal disimpan (semua perubahan di-rollback).
     */
    public function import(string $path, User $user): array
    {
        $spreadsheet = IOFactory::load($path);

        // Penandatangan sama untuk semua baris, jadi cukup di-resolve sekali (bukan per baris).
        $signer = MemoNumberSetting::resolveSigner($user);
        $businessUnit = $user->getBusinessUnitAttribute();

        $result = ['created' => 0, 'skipped' => [], 'needs_review' => 0];

        DB::transaction(function () use ($spreadsheet, $user, $signer, $businessUnit, &$result) {
            foreach ($spreadsheet->getSheetNames() as $sheetName) {
                if (MemoImportProfiles::shouldSkipSheet($sheetName)) {
                    continue;
                }

                $sheet = $spreadsheet->getSheetByName($sheetName);
                // formatData=false: ambil nilai numerik APA ADANYA, jangan dibungkus format
                // tampilan Excel (kalau true, sel berformat "#,##0.00" ikut jadi string
                // "668,000.00" - itu penyebab nominal salah saat import).
                $rows = $sheet->toArray(null, true, false, true);
                if (count($rows) < 2) {
                    continue; // cuma header / kosong
                }

                $headerRow = array_shift($rows);
                $columns = $this->mapHeaderColumns($headerRow);
                $profile = MemoImportProfiles::forSheet($sheetName);

                foreach ($rows as $rowIndex => $row) {
                    if ($this->isRowEmpty($row)) {
                        continue;
                    }

                    $needsReview = $this->createMemoFromRow($row, $columns, $profile, $sheetName, $user, $signer, $businessUnit);
                    if ($needsReview === null) {
                        $result['skipped'][] = "Sheet \"{$sheetName}\" baris {$rowIndex}: nama/tagihan tidak lengkap, dilewati";
                        continue;
                    }

                    $result['created']++;
                    if ($needsReview) {
                        $result['needs_review']++;
                    }
                }
            }
        });

        return $result;
    }

    /**
     * Buat 1 memo draft + 1 item dari 1 baris Excel.
     *
     * @return bool|null null = baris tidak valid (dilewati); true/false = memo dibuat,
     *                   nilainya menandakan perlu dicek manual (rekening belum lengkap) atau tidak.
     */
    private function createMemoFromRow(
        array $row,
        array $columns,
        array $profile,
        string $sheetName,
        User $user,
        array $signer,
        $businessUnit
    ): ?bool {
        $nama = $this->cell($row, $columns, 'Nama Karyawan');
        $tagihanRaw = $this->cell($row, $columns, 'Tagihan');
        $tagihan = $this->toNumber($tagihanRaw);

        if (empty($nama) || $tagihan === null) {
            return null;
        }

        $dynamicValues = array_map(
            fn ($colLabel) => $this->formatDynamicValue($colLabel, $this->cell($row, $columns, $colLabel)),
            $profile['dynamic_columns']
        );

        $nomorSuratAsal = $this->cell($row, $columns, 'Nomor Surat');
        $tanggalAsal = $this->cell($row, $columns, 'Tanggal');
        $catatanAsal = trim(collect([
            $nomorSuratAsal ? "Ref. nomor surat asal: {$nomorSuratAsal}" : null,
            $tanggalAsal ? "Tanggal asal: {$tanggalAsal}" : null,
        ])->filter()->implode(' | '));

        $bank = null;
        $atasNama = null;
        $noRek = null;
        $sertakanRekening = false;

        if ($profile['bank_source'] === 'fixed') {
            $bank = $profile['bank'];
            $atasNama = $profile['atas_nama'];
            $noRek = $profile['no_rek'];
            $sertakanRekening = true;
        } elseif ($profile['bank_source'] === 'row') {
            $bank = $this->cell($row, $columns, 'Nama Account');
            $atasNama = $this->cell($row, $columns, 'Nama Rekening');
            $noRek = $this->cell($row, $columns, 'No. Rekening');
            $sertakanRekening = !empty($bank);
        }

        $needsReview = $profile['needs_review'] || empty($bank);

        $instruksi = trim(collect([
            $profile['instruksi'],
            $needsReview ? '[PERLU DICEK] Rekening tujuan pembayaran belum bisa dipastikan otomatis dari sheet "' . $sheetName . '", mohon lengkapi sebelum submit.' : null,
            $catatanAsal,
        ])->filter()->implode("\n"));

        $memo = Memos::create([
            'perihal'                    => $profile['perihal'] . ' an. ' . $nama,
            'kepada'                     => $profile['kepada'],
            'dari'                       => $profile['dari'],
            'instruksi'                  => $instruksi,
            'bank'                       => $bank,
            'atas_nama'                  => $atasNama,
            'no_rek'                     => $noRek,
            'sertakan_rekening'          => $sertakanRekening,
            'paragraf_pembuka'           => $profile['paragraf_pembuka'],
            'penandatangan'              => $signer['penandatangan'],
            'jabatan'                    => $signer['jabatan'],
            'total_amount'               => $tagihan,
            'status'                     => 'draft',
            'business_unit'              => $businessUnit,
            'dynamic_columns_definition' => $profile['dynamic_columns'],
            'keterangan_label'           => $profile['keterangan_label'],
            'created_by'                 => $user->id,
            'expires_at'                 => now()->addDays(self::DRAFT_TTL_DAYS),
        ]);

        MemosItems::create([
            'memo_id'         => $memo->id,
            'nama'            => $nama,
            'dynamic_columns' => $dynamicValues,
            'tagihan'         => $tagihan,
            'sort_order'      => 0,
        ]);

        return $needsReview;
    }

    /**
     * Cocokkan header kolom Excel (misal "Nama Karyawan", "No. Rekening") ke huruf
     * kolom-nya (A, B, C, ...) supaya pembacaan baris tidak bergantung posisi tetap.
     */
    private function mapHeaderColumns(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $colLetter => $label) {
            $label = trim((string) $label);
            if ($label !== '') {
                $map[$label] = $colLetter;
            }
        }
        return $map;
    }

    /**
     * Kolom dinamis yang isinya nominal (mis. "Pembebanan Perusahaan", "Pembebanan
     * Karyawan") diformat pakai rupiah() supaya tampil rapi ("668.000"), bukan angka
     * mentah dari Excel. Kolom teks biasa (Beban PT, Unit Kerja, No. Invoice, dst)
     * dibiarkan apa adanya.
     */
    private function formatDynamicValue(string $colLabel, $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $isMoneyColumn = str_contains(strtolower($colLabel), 'pembebanan');
        if ($isMoneyColumn) {
            $number = $this->toNumber($value);
            if ($number !== null) {
                return $number == 0 ? '-' : rupiah($number);
            }
        }

        return (string) $value;
    }

    private function cell(array $row, array $columns, string $label)
    {
        $colLetter = $columns[$label] ?? null;
        if (!$colLetter) {
            return null;
        }
        $value = $row[$colLetter] ?? null;
        return is_string($value) ? trim($value) : $value;
    }

    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    private function toNumber($value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_string($value)) {
            $clean = preg_replace('/[^0-9,.-]/', '', $value);
            $clean = str_replace(['.', ','], ['', '.'], $clean);
            return is_numeric($clean) ? (float) $clean : null;
        }
        return null;
    }
}
