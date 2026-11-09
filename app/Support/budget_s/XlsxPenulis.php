<?php

namespace App\Support\budget_s;

/**
 * Penulis file .xlsx sederhana tanpa library tambahan (zip dibuat sendiri, tanpa ekstensi ZipArchive).
 * Mendukung gaya sel (huruf, warna, garis, format angka), lebar kolom, gabung sel, freeze pane, dan filter.
 *
 * Pemakaian: $x = new XlsxPenulis(); $l = $x->lembar('Nama'); $l->tambah([['Judul', 'judul']]); $isi = $x->hasil();
 */
class XlsxPenulis
{
    /** @var Lembar[] */
    protected array $lembar = [];

    /** Gaya sel: nama => [numFmtId, fontId, fillId, borderId, rata, bungkus] */
    public const GAYA = [
        'normal'   => [0, 0, 0, 0, null, false],
        'judul'    => [0, 3, 0, 0, null, false],
        'ket'      => [0, 4, 0, 0, null, false],
        'head'     => [0, 2, 2, 1, 'center', true],
        'teks'     => [0, 0, 0, 1, null, false],
        'angka'    => [3, 0, 0, 1, null, false],
        'sisa'     => [165, 0, 0, 1, null, false],
        'persen'   => [164, 0, 0, 1, 'center', false],
        'p_ok'     => [164, 1, 5, 1, 'center', false],
        'p_wn'     => [164, 1, 6, 1, 'center', false],
        'p_er'     => [164, 1, 7, 1, 'center', false],
        'strip'    => [0, 0, 0, 1, 'center', false],
        'label'    => [0, 1, 3, 1, null, false],
        'nilai'    => [3, 1, 0, 1, null, false],
        'nilai_p'  => [164, 1, 0, 1, 'right', false],
        'tot'      => [0, 1, 4, 1, null, false],
        'tot_angka' => [3, 1, 4, 1, null, false],
        'tot_sisa' => [165, 1, 4, 1, null, false],
        'tot_p'    => [164, 1, 4, 1, 'center', false],
    ];

    public function lembar(string $nama): Lembar
    {
        $nama = trim(preg_replace('/[\[\]:*?\/\\\\]/', ' ', $nama));
        $l = new Lembar(mb_substr($nama === '' ? 'Sheet' . (count($this->lembar) + 1) : $nama, 0, 31));
        $this->lembar[] = $l;

        return $l;
    }

    /** Isi file .xlsx sebagai string biner. */
    public function hasil(): string
    {
        $gayaIdx = array_flip(array_keys(self::GAYA));
        $n = count($this->lembar);

        $files = [];
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        $sheets = '';
        $rels = '';
        foreach ($this->lembar as $i => $l) {
            $no = $i + 1;
            $ct .= '<Override PartName="/xl/worksheets/sheet' . $no . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $sheets .= '<sheet name="' . self::esc($l->nama) . '" sheetId="' . $no . '" r:id="rId' . $no . '"/>';
            $rels .= '<Relationship Id="rId' . $no . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $no . '.xml"/>';
            $files['xl/worksheets/sheet' . $no . '.xml'] = $l->xml($gayaIdx, $i === 0);
        }
        $ct .= '</Types>';
        $rels .= '<Relationship Id="rId' . ($n + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $files = [
            '[Content_Types].xml' => $ct,
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<bookViews><workbookView activeTab="0"/></bookViews><sheets>' . $sheets . '</sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $rels . '</Relationships>',
            'xl/styles.xml' => $this->styles(),
        ] + $files;

        return self::zip($files);
    }

    protected function styles(): string
    {
        $xf = '';
        foreach (self::GAYA as [$fmt, $font, $fill, $border, $rata, $bungkus]) {
            $xf .= '<xf numFmtId="' . $fmt . '" fontId="' . $font . '" fillId="' . $fill . '" borderId="' . $border . '" xfId="0"'
                . ($fmt ? ' applyNumberFormat="1"' : '') . ($font ? ' applyFont="1"' : '') . ($fill ? ' applyFill="1"' : '') . ($border ? ' applyBorder="1"' : '')
                . ' applyAlignment="1"><alignment vertical="center"' . ($rata ? ' horizontal="' . $rata . '"' : '') . ($bungkus ? ' wrapText="1"' : '') . '/></xf>';
        }
        $tepi = '<left style="thin"><color rgb="FFCBD5E1"/></left><right style="thin"><color rgb="FFCBD5E1"/></right><top style="thin"><color rgb="FFCBD5E1"/></top><bottom style="thin"><color rgb="FFCBD5E1"/></bottom><diagonal/>';
        $isi = fn (string $rgb) => '<fill><patternFill patternType="solid"><fgColor rgb="FF' . $rgb . '"/><bgColor indexed="64"/></patternFill></fill>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2"><numFmt numFmtId="164" formatCode="0.0%"/><numFmt numFmtId="165" formatCode="#,##0;[Red]\-#,##0"/></numFmts>'
            . '<fonts count="5">'
            . '<font><sz val="11"/><color rgb="FF1F2937"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FF1F2937"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><b/><sz val="15"/><color rgb="FF1F3A5F"/><name val="Calibri"/><family val="2"/></font>'
            . '<font><i/><sz val="10"/><color rgb="FF64748B"/><name val="Calibri"/><family val="2"/></font>'
            . '</fonts>'
            . '<fills count="8"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . $isi('1F3A5F') . $isi('EEF2F7') . $isi('DCE4EE') . $isi('DCFCE7') . $isi('FEF3C7') . $isi('FEE2E2') . '</fills>'
            . '<borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border>' . $tepi . '</border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count(self::GAYA) . '">' . $xf . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    public static function esc($t): string
    {
        $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string) $t);

        return htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** ZIP tanpa kompresi (metode "store"), cukup untuk file .xlsx. */
    protected static function zip(array $files): string
    {
        $data = '';
        $pusat = '';
        $jumlah = 0;
        foreach ($files as $nama => $isi) {
            $crc = crc32($isi);
            $len = strlen($isi);
            $n = strlen($nama);
            $offset = strlen($data);
            $data .= pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0x21, $crc, $len, $len, $n, 0) . $nama . $isi;
            $pusat .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0x21, $crc, $len, $len, $n, 0, 0, 0, 0, 0, $offset) . $nama;
            $jumlah++;
        }

        return $data . $pusat . pack('VvvvvVVv', 0x06054b50, 0, 0, $jumlah, $jumlah, strlen($pusat), strlen($data), 0);
    }
}
