<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait AntaranHelpers
{
    /** Admin/CS yang boleh lihat semua transaksi, bukan cuma miliknya sendiri. */
    protected function hasAccessAll(): bool
    {
        $access = DB::table('tb_access_menu')->where('username', Auth::user()->username)->first();
        return $access && isset($access->akses_messenger_all) && (int) $access->akses_messenger_all === 1;
    }

    /** Baris tb_pelanggan milik user yang sedang login (dipakai sebagai pengirim maupun kurir). */
    protected function currentPelanggan()
    {
        return DB::table('tb_pelanggan')->where('id_login', Auth::id())->first();
    }

    /** Tambah 1 baris riwayat status ke kolom `waktu` (dipisah <br>, terbaru di bawah). */
    protected function appendWaktu(?string $old, string $label): string
    {
        $line = $label . ' &nbsp;&nbsp;(' . date('d-m-Y H:i:s') . ')';
        return trim((string) $old) ? $old . '<br>' . $line : $line;
    }

    /**
     * URL file foto_barang / gambar_akhir — SENGAJA lewat route Messenger
     * yang sudah ada (messenger.file), karena disk & pengecekan aksesnya
     * memang mau dipakai bersama, bukan dibuat ulang di sini.
     */
    protected function getFileUrl(?string $filename, string $type = 'foto_barang'): ?string
    {
        if (!$filename) return null;
        return route('messenger.file', ['type' => $type, 'filename' => $filename]);
    }

    /** Bikin baris tb_pelanggan baru kalau user login belum punya profil pelanggan/kurir. */
    protected function ensurePelanggan()
    {
        $pelanggan = $this->currentPelanggan();
        if ($pelanggan) return $pelanggan;

        $user = Auth::user();
        $id = DB::table('tb_pelanggan')->insertGetId([
            'id_login'           => Auth::id(),
            'nama_pelanggan'     => $user->name ?? $user->username ?? 'User_' . Auth::id(),
            'username_pelanggan' => $user->username ?? 'user_' . Auth::id(),
            'password'           => bcrypt('default123'),
            'no_hp_pelanggan'    => '0000000000',
            'email_pelanggan'    => $user->email ?? 'user' . Auth::id() . '@example.com',
            'gambar'             => '',
            'role_akses'         => 'Pelanggan',
            'bisnis_unit'        => 'Default',
            'departemen'         => 'Default',
            'pic'                => $user->name ?? $user->username ?? 'User_' . Auth::id(),
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        return DB::table('tb_pelanggan')->where('id_pelanggan', $id)->first();
    }
}
