<?php

if (!function_exists('terbilang')) {
    /**
     * Mengubah angka menjadi kalimat terbilang dalam bahasa Indonesia.
     * Mendukung hingga triliun (12 digit) DAN bagian desimal/sen (2 digit di belakang koma).
     * Kata "rupiah" dan "sen" SUDAH disertakan langsung di sini (mis. "... enam ratus
     * empat belas rupiah delapan puluh lima sen"), jadi pemanggil TIDAK perlu lagi
     * menambahkan ' rupiah)' sendiri — cukup bungkus dengan tanda kurung saja.
     */
    function terbilang($number)
    {
        $number = (float) $number;
        $isNegative = $number < 0;
        $number = abs($number);

        $rupiah = (int) floor($number);
        $sen = (int) round(($number - $rupiah) * 100);
        if ($sen >= 100) {
            $rupiah += 1;
            $sen -= 100;
        }

        $text = terbilangBulat($rupiah) . ' rupiah';

        if ($sen > 0) {
            $text .= ' ' . terbilangDuaDigit($sen) . ' sen';
        }

        return ($isNegative ? 'minus ' : '') . $text;
    }
}

if (!function_exists('terbilangBulat')) {
    /**
     * Bagian rupiah bulat — logic aslinya, tidak diubah sama sekali.
     */
    function terbilangBulat($number)
    {
        $number = (int) $number;
        if ($number == 0) return 'nol';

        $digits = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];
        $levels = ['', 'ribu', 'juta', 'miliar', 'triliun'];
        $belas = [
            11 => 'sebelas', 12 => 'dua belas', 13 => 'tiga belas',
            14 => 'empat belas', 15 => 'lima belas', 16 => 'enam belas',
            17 => 'tujuh belas', 18 => 'delapan belas', 19 => 'sembilan belas'
        ];

        $words = [];
        $level = 0;
        while ($number > 0) {
            $chunk = $number % 1000;
            if ($chunk > 0) {
                $chunkWords = [];
                $hundreds = floor($chunk / 100);
                $tens = $chunk % 100;

                if ($hundreds > 0) {
                    $chunkWords[] = ($hundreds == 1) ? 'seratus' : $digits[$hundreds] . ' ratus';
                }

                if ($tens > 0) {
                    if ($tens < 10) {
                        $chunkWords[] = $digits[$tens];
                    } elseif ($tens == 10) {
                        $chunkWords[] = 'sepuluh';
                    } elseif ($tens < 20) {
                        $chunkWords[] = $belas[$tens];
                    } else {
                        $tensDigit = floor($tens / 10);
                        $onesDigit = $tens % 10;
                        $chunkWords[] = $digits[$tensDigit] . ' puluh' . ($onesDigit ? ' ' . $digits[$onesDigit] : '');
                    }
                }

                // Penanganan khusus "seribu" untuk 1000
                if ($level == 1 && $chunk == 1 && $hundreds == 0 && $tens == 0) {
                    $chunkWords = ['seribu'];
                }

                $chunkStr = implode(' ', $chunkWords);
                if ($level > 0) $chunkStr .= ' ' . $levels[$level];
                array_unshift($words, $chunkStr);
            }
            $number = floor($number / 1000);
            $level++;
        }

        return implode(' ', $words);
    }
}

if (!function_exists('terbilangDuaDigit')) {
    /**
     * Ubah angka 0-99 (bagian sen, 2 digit di belakang koma) jadi kata-kata.
     * Contoh: 45 => "empat puluh lima", 5 => "lima".
     */
    function terbilangDuaDigit(int $n): string
    {
        $satuan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan'];

        if ($n <= 0) return 'nol';
        if ($n < 10) return $satuan[$n];
        if ($n == 10) return 'sepuluh';
        if ($n == 11) return 'sebelas';
        if ($n < 20) return $satuan[$n - 10] . ' belas';

        $puluh = intdiv($n, 10);
        $sisa = $n % 10;
        return $satuan[$puluh] . ' puluh' . ($sisa ? ' ' . $satuan[$sisa] : '');
    }
}

if (!function_exists('rupiah')) {
    /**
     * Format nominal untuk ditampilkan: "." sebagai pemisah ribuan, "," sebagai
     * pemisah desimal, dan bagian sen (2 digit di belakang koma) HANYA muncul
     * kalau nominalnya memang punya pecahan (mis. 668000 -> "668.000",
     * 1249339.47 -> "1.249.339,47"). Logic-nya sama persis dengan
     * `tagihanDisplay` di create.blade.php, supaya tampilannya konsisten
     * di semua halaman (index, show, pdf).
     */
    function rupiah($amount): string
    {
        $amount = (float) $amount;
        $decimals = ((float) $amount == floor((float) $amount)) ? 0 : 2;
        return number_format($amount, $decimals, ',', '.');
    }
}