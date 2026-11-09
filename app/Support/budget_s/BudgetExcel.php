<?php

namespace App\Support\budget_s;

/**
 * Menyusun file Excel (.xlsx) budget dan realisasi: sheet Ringkasan, Detail Item, dan Per Bulan.
 * Menerima data biasa (array), jadi tidak bergantung pada model.
 *
 * $d = [
 *   'tahun' => 2026, 'filter' => 'Semua data', 'oleh' => 'Nama', 'waktu' => '09/11/2026 10:00',
 *   'items' => [['item','kategori','sub','dept','bu','pt','pic','bulan' => [1..12 => float], 'budget','realisasi','persen']],
 *   'bulanan' => ['budget' => [12 angka], 'realisasi' => [12 angka]],
 * ]
 */
class BudgetExcel
{
    protected const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    protected const BULAN_PENDEK = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    public static function buat(array $d): string
    {
        $items = $d['items'];
        $tahun = $d['tahun'];
        $x = new XlsxPenulis();

        $tb = array_sum(array_column($items, 'budget'));
        $tr = array_sum(array_column($items, 'realisasi'));

        // ---------- Sheet 1: Ringkasan ----------
        $l = $x->lembar('Ringkasan')->lebar([44, 20, 20, 20, 20, 14]);
        $l->tambah([['Budget dan Realisasi ' . $tahun, 'judul']], 24);
        $l->tambah([['Filter: ' . $d['filter'], 'ket']]);
        $l->tambah([['Diunduh ' . $d['waktu'] . ' oleh ' . $d['oleh'], 'ket']]);
        $l->gabung('A1:F1');
        $l->tambah();
        $melebihi = count(array_filter($items, fn ($i) => $i['realisasi'] > $i['budget']));
        $l->tambah([['Total budget', 'label'], [$tb, 'nilai']]);
        $l->tambah([['Total realisasi', 'label'], [$tr, 'nilai']]);
        $l->tambah([['Sisa budget', 'label'], [$tb - $tr, 'tot_sisa']]);
        $l->tambah([['Persen terpakai', 'label'], [$tb > 0 ? $tr / $tb : '-', $tb > 0 ? self::gayaP($tr / $tb * 100) : 'strip']]);
        $l->tambah([['Jumlah item', 'label'], [count($items), 'nilai']]);
        $l->tambah([['Item melebihi budget', 'label'], [$melebihi, 'nilai']]);
        $l->tambah();
        $l->tambah([['Kategori', 'head'], ['Jumlah item', 'head'], ['Budget', 'head'], ['Realisasi', 'head'], ['Sisa', 'head'], ['% Terpakai', 'head']], 22);
        $kat = [];
        foreach ($items as $i) {
            $k = $i['kategori'];
            $kat[$k] ??= ['n' => 0, 'b' => 0.0, 'r' => 0.0];
            $kat[$k]['n']++;
            $kat[$k]['b'] += $i['budget'];
            $kat[$k]['r'] += $i['realisasi'];
        }
        foreach ($kat as $nama => $k) {
            $p = $k['b'] > 0 ? $k['r'] / $k['b'] : null;
            $l->tambah([[$nama, 'teks'], [$k['n'], 'angka'], [$k['b'], 'angka'], [$k['r'], 'angka'], [$k['b'] - $k['r'], 'sisa'], $p === null ? ['-', 'strip'] : [$p, self::gayaP($p * 100)]]);
        }
        $l->tambah([['Total', 'tot'], [count($items), 'tot_angka'], [$tb, 'tot_angka'], [$tr, 'tot_angka'], [$tb - $tr, 'tot_sisa'], $tb > 0 ? [$tr / $tb, 'tot_p'] : ['-', 'tot']]);
        $l->tambah();
        $l->tambah([['Catatan: Sisa negatif (merah) berarti realisasi melebihi budget. Warna persen: hijau di bawah 80%, kuning 80-100%, merah di atas 100%.', 'ket']]);
        $l->gabung('A' . ($l->nomorBerikut() - 1) . ':F' . ($l->nomorBerikut() - 1));

        // ---------- Sheet 2: Detail Item ----------
        $l = $x->lembar('Detail Item')->lebar(array_merge([38, 36, 30, 18, 16, 30, 18], array_fill(0, 12, 13), [17, 17, 17, 12]));
        $l->tambah([['Detail budget per item ' . $tahun, 'judul']], 24);
        $l->tambah([['Filter: ' . $d['filter'], 'ket']]);
        $l->tambah();
        $kepala = ['Item', 'Kategori', 'Subkategori', 'Departemen', 'BU', 'PT', 'PIC'];
        foreach (self::BULAN_PENDEK as $b) {
            $kepala[] = $b;
        }
        $kepala = array_merge($kepala, ['Total Budget', 'Realisasi', 'Sisa', '% Terpakai']);
        $hb = $l->tambah(array_map(fn ($t) => [$t, 'head'], $kepala), 24);
        foreach ($items as $i) {
            $baris = [[$i['item'], 'teks'], [$i['kategori'], 'teks'], [$i['sub'], 'teks'], [$i['dept'], 'teks'], [$i['bu'], 'teks'], [$i['pt'], 'teks'], [$i['pic'], 'teks']];
            for ($m = 1; $m <= 12; $m++) {
                $baris[] = [(float) ($i['bulan'][$m] ?? 0), 'angka'];
            }
            $baris[] = [(float) $i['budget'], 'angka'];
            $baris[] = [(float) $i['realisasi'], 'angka'];
            $baris[] = [(float) $i['budget'] - (float) $i['realisasi'], 'sisa'];
            $baris[] = $i['budget'] > 0 ? [$i['realisasi'] / $i['budget'], self::gayaP($i['realisasi'] / $i['budget'] * 100)] : [$i['realisasi'] > 0 ? 'budget 0' : '-', 'strip'];
            $l->tambah($baris);
        }
        $akhirData = $l->nomorBerikut() - 1;
        $tot = [['Total', 'tot']];
        for ($c = 1; $c < 7; $c++) {
            $tot[] = ['', 'tot'];
        }
        for ($m = 1; $m <= 12; $m++) {
            $tot[] = [array_sum(array_map(fn ($i) => (float) ($i['bulan'][$m] ?? 0), $items)), 'tot_angka'];
        }
        $tot[] = [$tb, 'tot_angka'];
        $tot[] = [$tr, 'tot_angka'];
        $tot[] = [$tb - $tr, 'tot_sisa'];
        $tot[] = $tb > 0 ? [$tr / $tb, 'tot_p'] : ['-', 'tot'];
        $l->tambah($tot);
        $l->gabung('A1:G1')->bekukan(1, $hb);
        if ($items) {
            $l->filter('A' . $hb . ':' . Lembar::kolom(count($kepala) - 1) . $akhirData);
        }

        // ---------- Sheet 3: Per Bulan (sama dengan grafik) ----------
        $l = $x->lembar('Per Bulan')->lebar([16, 20, 20, 20, 13, 22, 22, 15]);
        $l->tambah([['Budget vs realisasi per bulan ' . $tahun, 'judul']], 24);
        $l->tambah([['Filter: ' . $d['filter'], 'ket']]);
        $l->tambah();
        $hb = $l->tambah(array_map(fn ($t) => [$t, 'head'], ['Bulan', 'Budget', 'Realisasi', 'Sisa', '% Bulan', 'Budget Kumulatif', 'Realisasi Kumulatif', '% Kumulatif']), 24);
        $terakhir = -1;
        foreach (($d['bulanan']['realisasi'] ?? []) as $m => $v) {
            if ($v > 0) {
                $terakhir = $m;
            }
        }
        $cb = $cr = 0.0;
        for ($m = 0; $m < 12; $m++) {
            $b = (float) ($d['bulanan']['budget'][$m] ?? 0);
            $r = (float) ($d['bulanan']['realisasi'][$m] ?? 0);
            $cb += $b;
            $cr += $r;
            $pk = ($m <= $terakhir && $cb > 0) ? $cr / $cb : null;
            $l->tambah([
                [self::BULAN[$m], 'teks'], [$b, 'angka'], [$r, 'angka'], [$b - $r, 'sisa'],
                $b > 0 ? [$r / $b, self::gayaP($r / $b * 100)] : ['-', 'strip'],
                [$cb, 'angka'], [$m <= $terakhir ? $cr : '', $m <= $terakhir ? 'angka' : 'strip'],
                $pk === null ? ['-', 'strip'] : [$pk, self::gayaP($pk * 100)],
            ]);
        }
        $l->tambah([['Total', 'tot'], [$cb, 'tot_angka'], [$cr, 'tot_angka'], [$cb - $cr, 'tot_sisa'], $cb > 0 ? [$cr / $cb, 'tot_p'] : ['-', 'tot'], ['', 'tot'], ['', 'tot'], ['', 'tot']]);
        $l->tambah();
        $l->tambah([['% Kumulatif = total realisasi dari Januari sampai bulan itu dibagi total budget dari Januari sampai bulan itu (sama dengan garis kumulatif di grafik).', 'ket']]);
        $l->gabung('A1:H1')->bekukan(0, $hb);

        return $x->hasil();
    }

    /** Gaya sel persen sesuai status: hijau < 80%, kuning 80-100%, merah > 100%. */
    protected static function gayaP(float $p): string
    {
        return $p > 100 ? 'p_er' : ($p >= 80 ? 'p_wn' : 'p_ok');
    }
}
