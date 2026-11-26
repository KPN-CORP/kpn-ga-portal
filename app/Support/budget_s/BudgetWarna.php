<?php

namespace App\Support\budget_s;

/**
 * Warna tetap per tahun anggaran, supaya user langsung ngeh sedang membuka tahun berapa.
 * Warna dihitung dari angka tahunnya (tidak perlu diatur), jadi tahun baru otomatis punya warna sendiri
 * dan tahun yang berdekatan selalu berbeda warna.
 */
class BudgetWarna
{
    /** [warna tua, warna latar muda] */
    protected const PALET = [
        ['#1d4ed8', '#dbeafe'], // biru
        ['#7c3aed', '#ede9fe'], // ungu
        ['#047857', '#d1fae5'], // hijau
        ['#b45309', '#fef3c7'], // kuning tua
        ['#be185d', '#fce7f3'], // merah muda
        ['#0e7490', '#cffafe'], // toska
        ['#c2410c', '#ffedd5'], // oranye
        ['#4338ca', '#e0e7ff'], // nila
    ];

    /** @return array{warna: string, latar: string, status: string, jenis: string} */
    public static function tahun(int $tahun): array
    {
        [$tua, $muda] = self::PALET[$tahun % count(self::PALET)];
        $kini = (int) now()->year;

        return [
            'warna' => $tua,
            'latar' => $muda,
            'jenis' => $tahun === $kini ? 'berjalan' : ($tahun < $kini ? 'lalu' : 'depan'),
            'status' => $tahun === $kini ? 'Tahun berjalan' : ($tahun < $kini ? 'Tahun lalu' : 'Tahun depan'),
        ];
    }
}
