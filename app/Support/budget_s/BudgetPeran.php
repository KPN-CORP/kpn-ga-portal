<?php

namespace App\Support\budget_s;

use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetPicHead;
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
    protected static array $kepalaCache = [];
    protected static ?array $deptMap = null;
    protected static ?array $orangMap = null;

    public static function peran($user = null): ?string
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return null;
        }
        if (!array_key_exists($user->id, self::$cache)) {
            $peran = BudgetAkses::query()
                ->where('user_id', $user->id)->where('aktif', true)->value('peran');

            // PIC yang sudah dilepas dari semua Dept Head langsung kehilangan akses modul budget
            if ($peran === self::PIC
                && !BudgetPicHead::where('pic_user_id', $user->id)->where('aktif', true)->exists()) {
                $peran = null;
            }

            self::$cache[$user->id] = $peran;
        }
        return self::$cache[$user->id];
    }

    public static function lupakan($userId): void
    {
        unset(self::$cache[$userId], self::$kepalaCache[$userId]);
        self::$deptMap = self::$orangMap = null;
    }

    /** id Dept Head yang datanya boleh diakses user ini (Dept Head: dirinya; PIC: Dept Head-nya). Admin: kosong (akses semua). */
    public static function kepalaIds($user = null): array
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        if ($p === self::DEPT_HEAD) {
            return [(int) $user->id];
        }
        if ($p === self::PIC) {
            return self::$kepalaCache[$user->id] ??= BudgetPicHead::where('pic_user_id', $user->id)->where('aktif', true)
                ->pluck('dept_head_user_id')->map(fn ($x) => (int) $x)->all();
        }
        return [];
    }

    /** Dept Head pemilik data menurut peran: Dept Head dirinya, PIC salah satu Dept Head-nya, Admin yang dipilih. 0 = tidak jelas. */
    public static function tentukanKepala($user, $input): int
    {
        $p = self::peran($user);
        $in = (int) $input;
        if ($p === self::DEPT_HEAD) {
            return (int) $user->id;
        }
        if ($p === self::PIC) {
            $ids = self::kepalaIds($user);
            if ($in) {
                return in_array($in, $ids, true) ? $in : 0;
            }
            return count($ids) === 1 ? $ids[0] : 0;
        }
        return $p === self::ADMIN ? $in : 0;
    }

    protected static function muatDept(): void
    {
        if (self::$deptMap !== null) {
            return;
        }
        self::$deptMap = self::$orangMap = [];
        foreach (BudgetAkses::with('user')->where('peran', self::DEPT_HEAD)->get() as $a) {
            $orang = $a->user->name ?? $a->user->email ?? ('#' . $a->user_id);
            self::$orangMap[(int) $a->user_id] = $orang;
            self::$deptMap[(int) $a->user_id] = $a->nama_dept ?: $orang;
        }
    }

    /** Nama departemen/kelompok milik seorang Dept Head (jika belum diisi Admin: nama orangnya). */
    public static function namaDept($kepalaId): string
    {
        if (!$kepalaId) {
            return 'Umum';
        }
        self::muatDept();
        return self::$deptMap[(int) $kepalaId] ?? ('#' . $kepalaId);
    }

    public static function namaOrang($kepalaId): string
    {
        if (!$kepalaId) {
            return '-';
        }
        self::muatDept();
        return self::$orangMap[(int) $kepalaId] ?? ('#' . $kepalaId);
    }

    /** "Nama Dept (Nama Orang)" untuk pilihan dropdown. */
    public static function namaDeptLengkap($kepalaId): string
    {
        $d = self::namaDept($kepalaId);
        $o = self::namaOrang($kepalaId);
        return $d === $o ? $d : $d . ' (' . $o . ')';
    }

    /** Teks peran untuk sidebar: Dept Head tampil dengan nama departemennya. */
    public static function labelUser($user = null): string
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        return match ($p) {
            self::DEPT_HEAD => BudgetAkses::where('user_id', $user->id)->value('nama_dept') ?: 'Dept Head',
            self::PIC => 'PIC ' . collect(self::kepalaIds($user))->map(fn ($id) => self::namaDept($id))->implode(', '),
            default => self::label($p),
        };
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

    /** Dept Head pemilik item, atau PIC dari Dept Head tersebut (PIC punya akses yang sama dengan Dept Head-nya). */
    protected static function terkait(BudgetItem $item, $user, ?string $peran): bool
    {
        if ($peran !== self::DEPT_HEAD && $peran !== self::PIC) {
            return false;
        }
        return in_array((int) $item->dept_head_user_id, self::kepalaIds($user), true);
    }

    public static function bisaLihat(BudgetItem $item, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        return $p === self::ADMIN || ($p !== null && self::terkait($item, $user, $p));
    }

    /** Buat/ubah/hapus item dan mengatur PIC-nya: Admin, Dept Head pemilik, dan PIC-nya. */
    public static function bisaUbahItem(BudgetItem $item, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        return $p === self::ADMIN || self::terkait($item, $user, $p);
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