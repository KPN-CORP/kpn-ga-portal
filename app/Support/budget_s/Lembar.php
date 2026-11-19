<?php

namespace App\Support\budget_s;

/** Satu sheet pada XlsxPenulis. */
class Lembar
{
    public string $nama;
    protected array $baris = [];
    protected array $tinggi = [];
    protected array $lebar = [];
    protected array $gabung = [];
    protected ?array $bekuku = null;
    protected ?string $filter = null;

    public function __construct(string $nama)
    {
        $this->nama = $nama;
    }

    /** Lebar kolom (satuan karakter), urut dari kolom A. */
    public function lebar(array $w): self
    {
        $this->lebar = array_values($w);

        return $this;
    }

    /** Tambah satu baris. Tiap sel: nilai biasa, atau [nilai, 'gaya']. Mengembalikan nomor baris yang baru dibuat. */
    public function tambah(array $sel = [], ?float $tinggi = null): int
    {
        $this->baris[] = array_values($sel);
        $no = count($this->baris);
        if ($tinggi) {
            $this->tinggi[$no] = $tinggi;
        }

        return $no;
    }

    public function nomorBerikut(): int
    {
        return count($this->baris) + 1;
    }

    public function gabung(string $ref): self
    {
        $this->gabung[] = $ref;

        return $this;
    }

    /** Bekukan $kolom kolom pertama dan $baris baris pertama. */
    public function bekukan(int $kolom, int $baris): self
    {
        $this->bekuku = [$kolom, $baris];

        return $this;
    }

    public function filter(string $ref): self
    {
        $this->filter = $ref;

        return $this;
    }

    public static function kolom(int $i): string
    {
        $s = '';
        for ($i++; $i > 0; $i = intdiv($i - 1, 26)) {
            $s = chr(65 + ($i - 1) % 26) . $s;
        }

        return $s;
    }

    public function xml(array $gayaIdx, bool $aktif): string
    {
        $x = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetPr><tabColor rgb="FF1F3A5F"/><pageSetUpPr fitToPage="1"/></sheetPr>'
            . '<sheetViews><sheetView workbookViewId="0" showGridLines="0"' . ($aktif ? ' tabSelected="1"' : '') . '>';
        if ($this->bekuku) {
            [$k, $b] = $this->bekuku;
            $kiriAtas = self::kolom($k) . ($b + 1);
            if ($k > 0 && $b > 0) {
                $x .= '<pane xSplit="' . $k . '" ySplit="' . $b . '" topLeftCell="' . $kiriAtas . '" activePane="bottomRight" state="frozen"/>'
                    . '<selection pane="topRight"/><selection pane="bottomLeft"/><selection pane="bottomRight" activeCell="' . $kiriAtas . '" sqref="' . $kiriAtas . '"/>';
            } elseif ($b > 0) {
                $x .= '<pane ySplit="' . $b . '" topLeftCell="A' . ($b + 1) . '" activePane="bottomLeft" state="frozen"/><selection pane="bottomLeft" activeCell="A' . ($b + 1) . '" sqref="A' . ($b + 1) . '"/>';
            }
        }
        $x .= '</sheetView></sheetViews><sheetFormatPr defaultRowHeight="15"/>';
        if ($this->lebar) {
            $x .= '<cols>';
            foreach ($this->lebar as $i => $w) {
                $x .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . $w . '" customWidth="1"/>';
            }
            $x .= '</cols>';
        }
        $x .= '<sheetData>';
        foreach ($this->baris as $r => $sel) {
            $no = $r + 1;
            $x .= '<row r="' . $no . '"' . (isset($this->tinggi[$no]) ? ' ht="' . $this->tinggi[$no] . '" customHeight="1"' : '') . '>';
            foreach ($sel as $c => $isi) {
                $gaya = 'normal';
                if (is_array($isi)) {
                    $gaya = $isi[1] ?? 'normal';
                    $isi = $isi[0] ?? null;
                }
                $ref = self::kolom($c) . $no;
                $s = $gayaIdx[$gaya] ?? 0;
                if ($isi === null || $isi === '') {
                    $x .= '<c r="' . $ref . '" s="' . $s . '"/>';
                } elseif (is_int($isi) || is_float($isi)) {
                    $x .= '<c r="' . $ref . '" s="' . $s . '"><v>' . rtrim(rtrim(sprintf('%.6F', $isi), '0'), '.') . '</v></c>';
                } else {
                    $x .= '<c r="' . $ref . '" s="' . $s . '" t="inlineStr"><is><t xml:space="preserve">' . XlsxPenulis::esc($isi) . '</t></is></c>';
                }
            }
            $x .= '</row>';
        }
        $x .= '</sheetData>';
        if ($this->filter) {
            $x .= '<autoFilter ref="' . $this->filter . '"/>';
        }
        if ($this->gabung) {
            $x .= '<mergeCells count="' . count($this->gabung) . '">';
            foreach ($this->gabung as $g) {
                $x .= '<mergeCell ref="' . $g . '"/>';
            }
            $x .= '</mergeCells>';
        }

        return $x . '<pageMargins left="0.4" right="0.4" top="0.5" bottom="0.5" header="0.3" footer="0.3"/><pageSetup orientation="landscape" fitToWidth="1" fitToHeight="0"/></worksheet>';
    }
}
