<?php

namespace App\Support\Memos;

class MemoImportProfiles
{
    public const SKIP_SHEETS = ['info bank'];

    public const DEFAULT_DYNAMIC_COLUMNS = ['Beban PT', 'Unit Kerja', 'No. Invoice', 'Pembebanan Perusahaan', 'Pembebanan Karyawan', 'Tagihan'];

    public static function all(): array
    {
        return [
            'to employee' => [
                'perihal'          => 'Pembayaran Claim Pengobatan Karyawan',
                'kepada'           => 'Head Finance & Accounting',
                'dari'             => 'HCO Corporate',
                'paragraf_pembuka' => 'Mohon dapat dibayarkan claim medical karyawan dan dapat di transfer ke rekening sebagai berikut:',
                'instruksi'        => 'Demikian atas kerjasamanya, kami ucapkan terimakasih.',
                'keterangan_label' => 'Nama Karyawan',
                'dynamic_columns'  => ['Beban PT', 'Unit Kerja', 'No. Doc.', 'Pembebanan Perusahaan', 'Pembebanan Karyawan', 'Tagihan'],
                // bank tujuan BEDA-BEDA per baris (ada kolom Nama Account/Nama Rekening/No. Rekening di sheet ini)
                'bank_source'      => 'row',
                'needs_review'     => false,
            ],
            'rsmt sudirman' => [
                'perihal'          => 'Pembayaran Tagihan Biaya Pengobatan',
                'kepada'           => 'Head Finance & Accounting',
                'dari'             => 'HCO Corporate',
                'paragraf_pembuka' => 'Mohon dapat dibantu untuk pembayaran invoice biaya pengobatan karyawan di Rumah Sakit Murni Teguh Sudirman Jakarta dengan rincian sebagai berikut:',
                'instruksi'        => 'Demikian atas perhatiannya, kami ucapkan terima kasih.',
                'keterangan_label' => 'Nama Karyawan',
                'dynamic_columns'  => ['Beban PT', 'Unit Kerja', 'No. Invoice', 'Pembebanan Perusahaan', 'Pembebanan Karyawan', 'Tagihan'],
                'bank_source'      => 'fixed',
                'bank'             => 'BCA',
                'atas_nama'        => 'PT. SAHID SAHIRMAN MEMORIAL HOSPITAL',
                'no_rek'           => '526.530.1212',
                'needs_review'     => false,
            ],
            'rsmt pejaten' => [
                'perihal'          => 'Pembayaran Tagihan Biaya Pengobatan',
                'kepada'           => 'Head Finance & Accounting',
                'dari'             => 'HCO Corporate',
                'paragraf_pembuka' => 'Mohon dapat dibantu untuk pembayaran invoice biaya pengobatan karyawan di Rumah Sakit Murni Teguh Pejaten Jakarta dengan rincian sebagai berikut:',
                'instruksi'        => 'Demikian atas perhatiannya, kami ucapkan terima kasih.',
                'keterangan_label' => 'Nama Karyawan',
                'dynamic_columns'  => ['Beban PT', 'Unit Kerja', 'No. Invoice', 'Pembebanan Perusahaan', 'Pembebanan Karyawan', 'Tagihan'],
                'bank_source'      => 'fixed',
                'bank'             => 'BANK MANDIRI CAB JW MARRIOT',
                'atas_nama'        => 'PT MURNI SADAR, TBK',
                'no_rek'           => '1050022990000',
                'needs_review'     => false,
            ],
            'rsmt ciledug' => [
                'perihal'          => 'Pembayaran Tagihan Biaya Pengobatan',
                'kepada'           => 'Head Finance & Accounting',
                'dari'             => 'HCO Corporate',
                'paragraf_pembuka' => 'Mohon dapat dibantu untuk pembayaran invoice biaya pengobatan karyawan di Rumah Sakit Murni Teguh Ciledug dengan rincian sebagai berikut:',
                'instruksi'        => 'Demikian atas perhatiannya, kami ucapkan terima kasih.',
                'keterangan_label' => 'Nama Karyawan',
                'dynamic_columns'  => ['Beban PT', 'Unit Kerja', 'No. Invoice', 'Pembebanan Perusahaan', 'Pembebanan Karyawan', 'Tagihan'],
                'bank_source'      => 'fixed',
                'bank'             => 'BANK MANDIRI',
                'atas_nama'        => 'PT. MEDIKARYA AMINAH UTAMA',
                'no_rek'           => '127.00.0231112.2',
                'needs_review'     => false,
            ],
            'klinik gama' => [
                'perihal'          => 'Pembayaran Tagihan Biaya Pengobatan',
                'kepada'           => 'Head Finance & Accounting',
                'dari'             => 'HCO Corporate',
                'paragraf_pembuka' => 'Mohon dapat dibantu untuk pembayaran invoice biaya pengobatan karyawan di Murni Teguh Clinic Gama dengan rincian sebagai berikut:',
                'instruksi'        => 'Demikian atas perhatiannya, kami ucapkan terima kasih.',
                'keterangan_label' => 'Nama Karyawan',
                'dynamic_columns'  => ['Beban PT', 'Unit Kerja', 'No. Invoice', 'Pembebanan Perusahaan', 'Pembebanan Karyawan', 'Tagihan'],
                'bank_source'      => 'fixed',
                'bank'             => 'BANK MANDIRI',
                'atas_nama'        => 'PT BERKAT TEGUH UTAMA',
                'no_rek'           => '105-00138-8484-0',
                'needs_review'     => false,
            ],
            // Sheet berikut TIDAK punya surat master di folder Template, jadi bank
            // tujuan tidak bisa dipastikan otomatis -> ditandai needs_review supaya
            // admin cek & lengkapi manual sebelum memo di-submit (statusnya draft).
            'klinik arandra' => [
                'perihal'          => 'Pembayaran Tagihan Biaya Pengobatan',
                'kepada'           => 'Head Finance & Accounting',
                'dari'             => 'HCO Corporate',
                'paragraf_pembuka' => 'Mohon dapat dibantu untuk pembayaran invoice biaya pengobatan karyawan di Klinik Arandra dengan rincian sebagai berikut:',
                'instruksi'        => 'Demikian atas perhatiannya, kami ucapkan terima kasih.',
                'keterangan_label' => 'Nama Karyawan',
                'dynamic_columns'  => ['Beban PT', 'Unit Kerja', 'No. Invoice', 'Pembebanan Perusahaan', 'Pembebanan Karyawan', 'Tagihan'],
                'bank_source'      => 'manual',
                'needs_review'     => true,
            ],
            'other' => [
                'perihal'          => 'Pembayaran Tagihan Biaya Pengobatan',
                'kepada'           => 'Head Finance & Accounting',
                'dari'             => 'HCO Corporate',
                'paragraf_pembuka' => 'Mohon dapat dibantu untuk pembayaran tagihan biaya pengobatan karyawan dengan rincian sebagai berikut:',
                'instruksi'        => 'Demikian atas perhatiannya, kami ucapkan terima kasih.',
                'keterangan_label' => 'Nama Karyawan',
                'dynamic_columns'  => ['Beban PT', 'Unit Kerja', 'No. Invoice', 'Pembebanan Perusahaan', 'Pembebanan Karyawan', 'Tagihan'],
                'bank_source'      => 'manual',
                'needs_review'     => true,
            ],
        ];
    }

    public static function forSheet(string $sheetName): array
    {
        $key = strtolower(trim($sheetName));
        $profiles = self::all();

        if (isset($profiles[$key])) {
            return $profiles[$key];
        }

        return [
            'perihal'          => 'Pembayaran Tagihan Biaya Pengobatan',
            'kepada'           => 'Head Finance & Accounting',
            'dari'             => 'HCO Corporate',
            'paragraf_pembuka' => 'Mohon dapat dibantu untuk pembayaran tagihan biaya pengobatan karyawan dengan rincian sebagai berikut:',
            'instruksi'        => 'Demikian atas perhatiannya, kami ucapkan terima kasih.',
            'keterangan_label' => 'Nama Karyawan',
            'dynamic_columns'  => self::DEFAULT_DYNAMIC_COLUMNS,
            'bank_source'      => 'manual',
            'needs_review'     => true,
        ];
    }

    public static function shouldSkipSheet(string $sheetName): bool
    {
        return in_array(strtolower(trim($sheetName)), self::SKIP_SHEETS, true);
    }
}