<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Akses fitur ID Card yang hanya boleh dipakai ADMIN YANG DICEKLIS di database:
 * menu 'stok' dan 'kartu_pinjam'.
 *
 * Ceklisnya kolom di tb_access_menu (per username), sama seperti proses_idcard:
 *   - stok_idcard          = 1  -> boleh buka menu Stok
 *   - kartu_pinjam_idcard  = 1  -> boleh buka menu Kartu Pinjam
 *
 * TIDAK ada akses otomatis: superadmin (proses_idcard), admin BU, bahkan username 'admin'
 * tetap harus diceklis. Bila kolom belum dibuat / query error, hasilnya FALSE (tertutup).
 *
 * Dipakai oleh middleware, controller (lewat IDCardAccessTrait), service stok, dan sidebar.
 */
class IDCardAkses
{
    public const STOK         = 'stok';
    public const KARTU_PINJAM = 'kartu_pinjam';

    private const KOLOM = [
        self::STOK         => 'stok_idcard',
        self::KARTU_PINJAM => 'kartu_pinjam_idcard',
    ];

    /** @var array<string, bool> cache per request: "username:fitur" => bool */
    private static array $cache = [];

    public static function bisa(string $fitur, ?User $user = null): bool
    {
        $user = $user ?: Auth::user();
        if (!$user || empty($user->username) || !isset(self::KOLOM[$fitur])) {
            return false;
        }

        $key = $user->username . ':' . $fitur;
        if (!array_key_exists($key, self::$cache)) {
            self::$cache[$key] = self::cek((string) $user->username, self::KOLOM[$fitur]);
        }

        return self::$cache[$key];
    }

    public static function bisaUserId(int $userId, string $fitur): bool
    {
        $user = User::find($userId);
        return $user ? self::bisa($fitur, $user) : false;
    }

    private static function cek(string $username, string $kolom): bool
    {
        try {
            return DB::table('tb_access_menu')
                ->where('username', $username)
                ->where($kolom, 1)
                ->exists();
        } catch (\Throwable $e) {
            \Log::warning("IDCardAkses: kolom {$kolom} di tb_access_menu belum ada / error: " . $e->getMessage());
            return false;
        }
    }
}
