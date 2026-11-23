<?php

namespace App\Support\Memos;

/**
 * Susun header tabel rincian memo jadi 2 baris kalau ada pasangan kolom
 * "<Nama> Perusahaan" + "<Nama> Karyawan" yang berurutan (mis. "Pembebanan
 * Perusahaan" & "Pembebanan Karyawan") -> digabung jadi 1 header besar
 * "<Nama>" dengan 2 sub-kolom di bawahnya (persis seperti contoh tabel
 * "Pembebanan" -> Perusahaan / Karyawan).
 *
 * Tetap fleksibel: kalau dynamic_columns_definition tidak punya pasangan
 * seperti itu (mis. template Over Plafon / Lunas Plafon), otomatis balik ke
 * header 1 baris biasa seperti sebelumnya — tidak perlu diubah manual per
 * template.
 */
class MemoItemsTableColumns
{
    /**
     * @return array<int, array{type:string,label:string,indexes:array<int,int>,sub?:array<int,string>}>
     */
    public static function build(array $dynamicColumns): array
    {
        $groups = [];
        $i = 0;
        $n = count($dynamicColumns);

        while ($i < $n) {
            $groupName = null;
            if (
                $i + 1 < $n
                && self::isPairedGroup($dynamicColumns[$i], $dynamicColumns[$i + 1], $groupName)
            ) {
                $groups[] = [
                    'type'    => 'group',
                    'label'   => $groupName,
                    'sub'     => ['Perusahaan', 'Karyawan'],
                    'indexes' => [$i, $i + 1],
                ];
                $i += 2;
                continue;
            }

            $groups[] = [
                'type'    => 'single',
                'label'   => $dynamicColumns[$i],
                'indexes' => [$i],
            ];
            $i++;
        }

        return $groups;
    }

    public static function hasGroups(array $columnGroups): bool
    {
        foreach ($columnGroups as $g) {
            if ($g['type'] === 'group') {
                return true;
            }
        }
        return false;
    }

    /**
     * Kolom dianggap "nominal" kalau namanya persis "Tagihan" atau mengandung
     * kata "Pembebanan" (Pembebanan Perusahaan/Karyawan). Dipakai untuk:
     * rata kanan + nowrap, dan supaya baris TOTAL tahu kolom mana yang perlu
     * dijumlah.
     */
    public static function isMoneyColumn(string $label): bool
    {
        $label = strtolower(trim($label));
        return $label === 'tagihan' || str_contains($label, 'pembebanan');
    }

    /**
     * Cek apakah "Tagihan" sudah dimasukkan sebagai salah satu kolom di
     * dynamic_columns_definition (mis. supaya posisinya bisa di tengah,
     * sebelum grup Pembebanan — sesuai urutan surat masternya). Kalau tidak
     * ada, tabel tetap pakai kolom "Tagihan" tetap di paling akhir seperti
     * sebelumnya (flexible/backward-compatible untuk template lain).
     */
    public static function hasInlineTagihan(array $dynamicColumns): bool
    {
        foreach ($dynamicColumns as $label) {
            if (strcasecmp(trim($label), 'Tagihan') === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Jumlah kolom "No" + Keterangan + semua kolom dinamis yang BUKAN nominal
     * -> ini yang jadi colspan label "TOTAL" di baris penjumlahan.
     */
    public static function labelColspan(array $dynamicColumns): int
    {
        $nonMoney = array_filter($dynamicColumns, fn ($c) => !self::isMoneyColumn($c));
        return 2 + count($nonMoney);
    }

    /**
     * Jumlahkan nilai kolom nominal (yang disimpan sebagai teks terformat,
     * mis. "346.500" atau "-") di seluruh item memo, dipakai untuk baris TOTAL.
     */
    public static function sumColumn(iterable $items, int $columnIndex): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $dyn = is_array($item->dynamic_columns) ? $item->dynamic_columns : [];
            $sum += self::parseFormattedNumber($dyn[$columnIndex] ?? null);
        }
        return $sum;
    }

    /**
     * Kebalikan dari rupiah(): balikin "346.500" / "1.249.339,47" / "1249339.47" jadi float.
     */
    public static function parseFormattedNumber($value): float
    {
        if ($value === null || $value === '' || $value === '-') {
            return 0.0;
        }

        // Kalau tipe aslinya sudah numeric asli (int/float dari DB), langsung
        // pakai apa adanya — TIDAK boleh lewat is_numeric($value) untuk string,
        // karena string format Indonesia seperti "378.720" (titik = pemisah
        // ribuan) juga valid menurut is_numeric() PHP dan akan salah kebaca
        // jadi 378.72 (titik dianggap desimal ala Barat).
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $str = trim((string) $value);
        if ($str === '') {
            return 0.0;
        }

        $hasComma = str_contains($str, ',');
        $hasDot   = str_contains($str, '.');

        // Format Indonesia lengkap: "1.249.339,47" -> titik = ribuan, koma = desimal.
        if ($hasComma && $hasDot) {
            $clean = str_replace('.', '', $str);
            $clean = str_replace(',', '.', $clean);
            return is_numeric($clean) ? (float) $clean : 0.0;
        }

        // Cuma koma, tanpa titik: "1249339,47" -> koma = desimal.
        if ($hasComma && !$hasDot) {
            $clean = str_replace(',', '.', $str);
            return is_numeric($clean) ? (float) $clean : 0.0;
        }

        // Cuma titik, tanpa koma — AMBIGU, bisa dua kemungkinan:
        //   "378.720"      -> titik = pemisah ribuan (nilai bulat 378720)
        //   "1249339.47"   -> titik = desimal biasa (nilai mentah dari DB/JS, dua digit di belakang koma)
        // Dibedakan dari jumlah digit setelah titik TERAKHIR: 3 digit -> ribuan,
        // 1-2 digit -> desimal (konsisten dengan rupiah() yang selalu pakai 2 digit sen).
        if ($hasDot && !$hasComma) {
            if (preg_match('/^-?\d{1,3}(\.\d{3})+$/', $str)) {
                $clean = str_replace('.', '', $str);
                return is_numeric($clean) ? (float) $clean : 0.0;
            }
            if (preg_match('/^-?\d+\.\d{1,2}$/', $str)) {
                return (float) $str;
            }
            // Fallback: default ke perilaku format Indonesia (titik = ribuan).
            $clean = str_replace('.', '', $str);
            return is_numeric($clean) ? (float) $clean : 0.0;
        }

        return is_numeric($str) ? (float) $str : 0.0;
    }

    private static function isPairedGroup(string $a, string $b, ?string &$groupName): bool
    {
        $groupName = null;

        if (
            preg_match('/^(.+?)\s+Perusahaan$/i', trim($a), $ma)
            && preg_match('/^(.+?)\s+Karyawan$/i', trim($b), $mb)
            && strcasecmp(trim($ma[1]), trim($mb[1])) === 0
        ) {
            $groupName = trim($ma[1]);
            return true;
        }

        return false;
    }
}