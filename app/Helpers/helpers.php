<?php

if (!function_exists('terbilang')) {
    /**
     * Mengubah angka menjadi kalimat terbilang dalam bahasa Indonesia
     * Mendukung hingga triliun (12 digit)
     */
    function terbilang($number)
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