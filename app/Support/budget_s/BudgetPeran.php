<?php

namespace App\Support\budget_s;

use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetTahun;

/**
 * Satu-satunya sumber hak akses modul budget (dibaca dari tabel budget_akses, BUKAN users.role).
 * Admin: akses penuh, termasuk mengubah data dan tetap bisa mengisi budget pada tahun yang dikunci.
 */
class BudgetPeran
{
    public const ADMIN = 'admin';
    public const DEPT_HEAD = 'dept_head';
    public const PIC = 'pic';

    protected static array $cache = [];

    public static function peran($user = null): ?string
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return null;
        }
        if (!array_key_exists($user->id, self::$cache)) {
            self::$cache[$user->id] = BudgetAkses::query()
                ->where('user_id', $user->id)->where('aktif', true)->value('peran');
        }
        return self::$cache[$user->id];
    }

    public static function lupakan($userId): void
    {
        unset(self::$cache[$userId]);
    }

    public static function isAdmin($user = null): bool
    {
        return self::peran($user) === self::ADMIN;
    }

    public static function isDeptHead($user = null): bool
    {
        return self::peran($user) === self::DEPT_HEAD;
    }

    public static function isPic($user = null): bool
    {
        return self::peran($user) === self::PIC;
    }

    public static function label(?string $peran): string
    {
        return match ($peran) {
            self::ADMIN => 'Admin',
            self::DEPT_HEAD => 'Dept Head',
            self::PIC => 'PIC',
            default => '-',
        };
    }

    /** Admin, atau Dept Head pemilik item, atau PIC yang ditunjuk pada item. */
    protected static function terkait(BudgetItem $item, $user, ?string $peran): bool
    {
        if ($peran === self::DEPT_HEAD) {
            return (int) $item->dept_head_user_id === (int) $user->id;
        }
        if ($peran === self::PIC) {
            return $item->pic_user_id !== null && (int) $item->pic_user_id === (int) $user->id;
        }
        return false;
    }

    public static function bisaLihat(BudgetItem $item, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        return $p === self::ADMIN || ($p !== null && self::terkait($item, $user, $p));
    }

    /** Buat/ubah/hapus item dan mengatur PIC-nya. PIC tidak boleh. */
    public static function bisaUbahItem(BudgetItem $item, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        return $p === self::ADMIN || ($p === self::DEPT_HEAD && self::terkait($item, $user, $p));
    }

    /** Isi budget bulanan. Tahun terkunci menutup Dept Head dan PIC, tetapi tidak Admin. */
    public static function bisaIsiBudget(BudgetItem $item, int $tahun, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        if ($p === self::ADMIN) {
            return true;
        }
        if ($p === null || BudgetTahun::terkunci($tahun)) {
            return false;
        }
        return self::terkait($item, $user, $p);
    }

    /** Input/ubah/hapus tagihan (tidak terpengaruh kunci tahun). */
    public static function bisaInputTagihan(BudgetItem $item, $user = null): bool
    {
        return self::bisaLihat($item, $user);
    }
}
