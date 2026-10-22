<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hak akses E-FinGrab, dua gerbang:
 *  1. Menu: pengguna punya fingrab_dash = 1 di tabel akses menu (ikon di GA Portal). Username 'admin' lolos otomatis.
 *  2. Cakupan data: baris di fingrab_accesbu (user_id, bisnis_unit_id).
 *       - baris BU 'KPN Corporation' atau username 'admin' -> semua Bisnis unit, boleh upload
 *       - baris BU lain                                    -> hanya BU tersebut
 * Gagal di salah satu gerbang -> halaman resources/views/no-access.blade.php (HTTP 403).
 * Menyembunyikan ikon saja tidak cukup (URL bisa diketik langsung), jadi gerbang 1 juga dicek di server.
 */
class FinGrabAccess
{
    /** @return array{allowed: bool, all: bool, ids: int[], label: string} */
    public static function scope($user): array
    {
        $none = ['allowed' => false, 'all' => false, 'ids' => [], 'label' => ''];
        if (!$user) {
            return $none;
        }

        $username = $user->username ?? null;
        $isAdmin = $username === config('fingrab.superadmin_username', 'admin');

        if (!$isAdmin && !self::hasMenu($username)) {
            return $none;
        }

        $ids = DB::table(config('fingrab.access_table', 'fingrab_accesbu'))
            ->where('user_id', $user->id)->pluck('bisnis_unit_id')
            ->map(fn ($v) => (int) $v)->unique()->values()->all();

        $corpId = DB::table('tb_bisnis_unit')->where('nama_bisnis_unit', config('fingrab.corporate_bu'))->value('id_bisnis_unit');
        $all = $isAdmin || ($corpId !== null && in_array((int) $corpId, $ids, true));

        if (!$all && empty($ids)) {
            return $none;
        }

        $label = 'Admin Corporate: semua Bisnis unit';
        if (!$all) {
            $names = DB::table('tb_bisnis_unit')->whereIn('id_bisnis_unit', $ids)->pluck('nama_bisnis_unit')->all();
            $label = 'Admin: ' . implode(', ', $names);
        }

        return ['allowed' => true, 'all' => $all, 'ids' => $ids, 'label' => $label];
    }

    /** Tampilkan resources/views/no-access.blade.php dengan pesan di session('error'). */
    public static function deny(string $message)
    {
        session()->now('error', $message);
        return response()->view('no-access', [], 403);
    }

    private static function hasMenu(?string $username): bool
    {
        $table = config('fingrab.menu_table', 'tb_access_menu');
        $col = config('fingrab.menu_column', 'fingrab_dash');

        return $username && Schema::hasColumn($table, $col)
            && DB::table($table)->where(config('fingrab.menu_key', 'username'), $username)->where($col, 1)->exists();
    }
}
