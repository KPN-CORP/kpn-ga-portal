<?php

namespace App\Support\budget_s;

use ZipArchive;

/**
 * Penulis .xlsx sederhana tanpa paket tambahan (hanya ext-zip bawaan PHP) untuk unduhan isian form.
 * Satu sheet: baris judul (beku + filter), baris data, dan baris total yang ikut berubah saat difilter di Excel.
 *
 * $judul  : array teks judul kolom
 * $tipe   : array tipe per kolom: 'teks' | 'angka' | 'tanggal' | 'uang'
 * $baris  : array baris; tiap sel = nilai mentah (null = kosong; tanggal berformat Y-m-d)
 * $total  : indeks kolom (0-based) yang dijumlahkan di baris total
 */
class BudgetIsianExcel
{
    public static function buat(string $namaSheet, array $judul, array $tipe, array $baris, array $total = []): string
    {
        $nKol = count($judul);
        $nBaris = count($baris);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:' . static::huruf($nKol - 1) . ($nBaris + 2) . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>' . static::lebar($judul, $tipe, $baris) . '</cols><sheetData>';

        // Baris judul
        $xml .= '<row r="1">';
        foreach ($judul as $c => $j) {
            $xml .= static::teks(static::huruf($c) . '1', (string) $j, 1);
        }
        $xml .= '</row>';

        // Baris data
        foreach ($baris as $r => $sel) {
            $no = $r + 2;
            $xml .= '<row r="' . $no . '">';
            foreach ($judul as $c => $_) {
                $xml .= static::sel(static::huruf($c) . $no, $tipe[$c] ?? 'teks', $sel[$c] ?? null);
            }
            $xml .= '</row>';
        }

        // Baris total (SUBTOTAL: hanya menjumlah baris yang tampil bila difilter)
        $noTotal = $nBaris + 2;
        $xml .= '<row r="' . $noTotal . '">';
        foreach ($judul as $c => $_) {
            $ref = static::huruf($c) . $noTotal;
            if ($c === 0) {
                $xml .= static::teks($ref, 'TOTAL', 4);
            } elseif (in_array($c, $total, true)) {
                $kol = static::huruf($c);
                $cache = 0.0;
                foreach ($baris as $sel) {
                    $cache += (float) ($sel[$c] ?? 0);
                }
                $f = $nBaris > 0 ? 'SUBTOTAL(109,' . $kol . '2:' . $kol . ($nBaris + 1) . ')' : '0';
                $xml .= '<c r="' . $ref . '" s="5"><f>' . $f . '</f><v>' . static::angka($cache) . '</v></c>';
            } else {
                $xml .= '<c r="' . $ref . '" s="4"/>';
            }
        }
        $xml .= '</row></sheetData>';
        if ($nBaris > 0) {
            $xml .= '<autoFilter ref="A1:' . static::huruf($nKol - 1) . ($nBaris + 1) . '"/>';
        }
        $xml .= '</worksheet>';

        return static::zip($namaSheet, $xml);
    }

    protected static function sel(string $ref, string $tipe, $v): string
    {
        if ($v === null || $v === '') {
            return '<c r="' . $ref . '"/>';
        }
        if ($tipe === 'uang') {
            return '<c r="' . $ref . '" s="2"><v>' . static::angka((float) $v) . '</v></c>';
        }
        if ($tipe === 'angka' && is_numeric($v)) {
            return '<c r="' . $ref . '"><v>' . static::angka((float) $v) . '</v></c>';
        }
        if ($tipe === 'tanggal' && preg_match('/^(\d{4})-(\d{2})-(\d{2})/', (string) $v, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            $serial = (int) (gmmktime(0, 0, 0, (int) $m[2], (int) $m[3], (int) $m[1]) / 86400) + 25569;
            return '<c r="' . $ref . '" s="3"><v>' . $serial . '</v></c>';
        }
        return static::teks($ref, (string) $v, 0);
    }

    protected static function teks(string $ref, string $t, int $gaya): string
    {
        $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $t);
        $s = $gaya ? ' s="' . $gaya . '"' : '';
        return '<c r="' . $ref . '"' . $s . ' t="inlineStr"><is><t xml:space="preserve">'
            . htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
    }

    protected static function angka(float $n): string
    {
        return rtrim(rtrim(number_format($n, 6, '.', ''), '0'), '.') ?: '0';
    }

    protected static function huruf(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }
        return $s;
    }

    protected static function lebar(array $judul, array $tipe, array $baris): string
    {
        $o = '';
        foreach ($judul as $c => $j) {
            $maks = mb_strlen((string) $j) + 3;
            foreach (array_slice($baris, 0, 200) as $sel) {
                $v = $sel[$c] ?? null;
                $maks = max($maks, in_array($tipe[$c] ?? '', ['uang', 'tanggal'], true) ? 14 : min(60, mb_strlen((string) $v) + 2));
            }
            $o .= '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . min(60, max(10, $maks)) . '" customWidth="1"/>';
        }
        return $o;
    }

    protected static function zip(string $namaSheet, string $sheet): string
    {
        $nama = htmlspecialchars(mb_substr(preg_replace('/[\[\]\:\*\?\/\\\\]/', ' ', $namaSheet) ?: 'Isian', 0, 31), ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $head = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';

        $berkas = [
            '[Content_Types].xml' => $head . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>',
            '_rels/.rels' => $head . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => $head . '<workbook xmlns="' . $ns . '" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="' . $nama . '" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => $head . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
            // Gaya: 0 biasa | 1 judul | 2 uang #,##0 | 3 tanggal dd/mm/yyyy | 4 total (teks, tebal) | 5 total (uang, tebal)
            'xl/styles.xml' => $head . '<styleSheet xmlns="' . $ns . '">'
                . '<numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/></numFmts>'
                . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
                . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
                . '<fill><patternFill patternType="solid"><fgColor rgb="FFE5E7EB"/><bgColor indexed="64"/></patternFill></fill></fills>'
                . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
                . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
                . '<cellXfs count="6">'
                . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
                . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf>'
                . '<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
                . '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="left"/></xf>'
                . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
                . '<xf numFmtId="3" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyNumberFormat="1"/>'
                . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
            'xl/worksheets/sheet1.xml' => $sheet,
        ];

        $tmp = tempnam(sys_get_temp_dir(), 'isian');
        $z = new ZipArchive();
        $z->open($tmp, ZipArchive::OVERWRITE);
        foreach ($berkas as $path => $isi) {
            $z->addFromString($path, $isi);
        }
        $z->close();
        $bin = file_get_contents($tmp);
        @unlink($tmp);

        return $bin;
    }
}
