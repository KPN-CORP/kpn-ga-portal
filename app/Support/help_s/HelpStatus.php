<?php

namespace App\Support\help_s;

/**
 * Satu sumber istilah, warna, dan teks status tiket Help Desk.
 * Dipakai daftar tiket, detail pelapor, halaman proses petugas, dan log.
 */
class HelpStatus
{
    public const LABEL = [
        'OPEN'         => 'Menunggu ditangani',
        'ON_PROCESS'   => 'Sedang diproses',
        'WAITING'      => 'Menunggu',
        'DONE'         => 'Selesai',
        'CLOSED'       => 'Ditutup',
        'HELP_GA_CORP' => 'Diteruskan ke GA Corp',
    ];

    public const WARNA = [
        'OPEN'         => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        'ON_PROCESS'   => 'bg-blue-100 text-blue-800 border-blue-200',
        'WAITING'      => 'bg-orange-100 text-orange-800 border-orange-200',
        'DONE'         => 'bg-green-100 text-green-800 border-green-200',
        'CLOSED'       => 'bg-gray-100 text-gray-800 border-gray-200',
        'HELP_GA_CORP' => 'bg-purple-100 text-purple-800 border-purple-200',
    ];

    /**
     * Jenis tunggu untuk status WAITING.
     * label         : untuk petugas / laporan
     * label_pelapor : untuk pelapor
     * tindakan      : true bila pelapor harus berbuat sesuatu
     */
    public const JENIS_TUNGGU = [
        'USER' => [
            'label'         => 'Menunggu pelapor',
            'label_pelapor' => 'Menunggu Anda',
            'icon'          => 'fa-comment-dots',
            'warna'         => 'bg-orange-100 text-orange-800 border-orange-200',
            'isi'           => 'Petugas butuh info tambahan sebelum lanjut.',
            'bantuan'       => 'Balas di kolom diskusi agar tiket dilanjutkan.',
            'tindakan'      => true,
        ],
        'PENGADAAN' => [
            'label'         => 'Menunggu pengadaan',
            'label_pelapor' => 'Menunggu pengadaan',
            'icon'          => 'fa-cart-shopping',
            'warna'         => 'bg-cyan-100 text-cyan-800 border-cyan-200',
            'isi'           => 'GA sedang memproses pembelian barang.',
            'bantuan'       => 'Tidak ada tindakan dari Anda.',
            'tindakan'      => false,
        ],
        'VENDOR' => [
            'label'         => 'Menunggu vendor',
            'label_pelapor' => 'Dikerjakan vendor',
            'icon'          => 'fa-truck',
            'warna'         => 'bg-indigo-100 text-indigo-800 border-indigo-200',
            'isi'           => 'Pekerjaan diserahkan ke pihak luar.',
            'bantuan'       => 'Tidak ada tindakan dari Anda.',
            'tindakan'      => false,
        ],
    ];

    public static function daftarJenis(): array
    {
        return array_keys(self::JENIS_TUNGGU);
    }

    public static function jenis(?string $jenis): ?array
    {
        return $jenis !== null ? (self::JENIS_TUNGGU[$jenis] ?? null) : null;
    }

    public static function label(string $status, ?string $jenis = null, bool $untukPelapor = false): string
    {
        if ($status === 'WAITING' && ($j = self::jenis($jenis))) {
            return $untukPelapor ? $j['label_pelapor'] : $j['label'];
        }

        return self::LABEL[$status] ?? $status;
    }

    /**
     * Gaya kode untuk layar petugas: "ON PROCESS", "WAITING · Pengadaan".
     */
    public static function labelKode(string $status, ?string $jenis = null): string
    {
        $kode = str_replace('_', ' ', $status);

        if ($status === 'WAITING' && ($j = self::jenis($jenis))) {
            return $kode . ' · ' . ucfirst(str_replace('Menunggu ', '', $j['label']));
        }

        return $kode;
    }

    public static function warna(string $status, ?string $jenis = null): string
    {
        if ($status === 'WAITING' && ($j = self::jenis($jenis))) {
            return $j['warna'];
        }

        return self::WARNA[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
    }
}
