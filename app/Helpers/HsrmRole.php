<?php

namespace App\Helpers;

use App\Models\HSRM\HsrmUserRole;

/**
 * Satu pintu untuk semua pengecekan role HSRM.
 *
 *  superadmin : semua akses admin + kelola akses role (halaman Role Access)
 *  admin      : lihat semua area + tambah/ubah/hapus/approve
 *  management : lihat semua area, READ-ONLY (tidak bisa tambah/ubah/hapus/approve)
 *  pic        : hanya area miliknya (perilaku lama, tidak berubah)
 */
class HsrmRole
{
    public const PIC        = 'pic';
    public const ADMIN      = 'admin';
    public const MANAGEMENT = 'management';
    public const SUPERADMIN = 'superadmin';

    public const ALL = [self::PIC, self::ADMIN, self::MANAGEMENT, self::SUPERADMIN];

    // Role tanpa area tertentu (berlaku global)
    public const GLOBAL = [self::MANAGEMENT, self::SUPERADMIN];

    // Urutan prioritas jika satu user punya beberapa baris role
    private const PRIORITY = [self::SUPERADMIN, self::ADMIN, self::MANAGEMENT, self::PIC];

    public static function current(): ?string
    {
        return session('hsrm_role');
    }

    public static function isSuperadmin(): bool
    {
        return self::current() === self::SUPERADMIN;
    }

    /** Level admin: admin ATAU superadmin (superadmin mewarisi semua hak admin). */
    public static function isAdmin(): bool
    {
        return in_array(self::current(), [self::ADMIN, self::SUPERADMIN], true);
    }

    public static function isManagement(): bool
    {
        return self::current() === self::MANAGEMENT;
    }

    /** Boleh melihat data SEMUA area (admin, superadmin, management). */
    public static function canViewAll(): bool
    {
        return in_array(self::current(), [self::SUPERADMIN, self::ADMIN, self::MANAGEMENT], true);
    }

    /** Boleh melakukan aksi tulis (tambah/ubah/hapus/approve). Management tidak. */
    public static function canWrite(): bool
    {
        return in_array(self::current(), [self::SUPERADMIN, self::ADMIN, self::PIC], true);
    }

    public static function abortUnlessCanWrite(string $message = 'Your role has view-only access.'): void
    {
        if (!self::canWrite()) {
            abort(403, $message);
        }
    }

    /**
     * Tentukan role HSRM tertinggi milik user (untuk disimpan ke session('hsrm_role') saat login).
     */
    public static function resolveForUser($user): ?string
    {
        if (!$user) {
            return null;
        }
        $roles = HsrmUserRole::where('user_id', $user->id)->pluck('role')->all();
        foreach (self::PRIORITY as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }
        return null;
    }
}
