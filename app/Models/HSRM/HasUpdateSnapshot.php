<?php

namespace App\Models\HSRM;

use Illuminate\Support\Arr;

/**
 * Menyimpan snapshot data lama (versi verified) saat sebuah record di-update,
 * supaya kalau update ditolak admin/superadmin, data lama bisa dikembalikan.
 *
 * Letak file: app/Models/HSRM/HasUpdateSnapshot.php
 * Model yang memakai trait ini wajib punya kolom JSON `previous_data`
 * (cast 'array') dan method static snapshotFields().
 */
trait HasUpdateSnapshot
{
    /** Daftar kolom yang disimpan di snapshot. */
    abstract public static function snapshotFields(): array;

    /** True jika record sedang punya update yang menunggu keputusan dan data lamanya tersimpan. */
    public function hasPendingUpdate(): bool
    {
        // Snapshot hanya dianggap aktif jika record memang sedang menunggu keputusan update.
        // Ini melindungi dari snapshot basi (mis. record di-approve oleh versi aplikasi yang belum punya fitur ini).
        return !empty($this->previous_data)
            && $this->pending_action === 'update'
            && in_array($this->status_verif, ['pending', 'revision'], true);
    }

    /**
     * Ambil snapshot untuk disimpan sebelum update.
     * - Sudah ada snapshot (update berulang sebelum diputuskan) -> snapshot pertama dipertahankan.
     * - Data saat ini berstatus verified -> snapshot dari nilai mentah di DB.
     * - Selain itu (data baru / belum pernah verified) -> null, tidak ada yang bisa dikembalikan.
     * Panggil SEBELUM $model->update($data).
     */
    public function takeSnapshotIfNeeded(): ?array
    {
        if ($this->hasPendingUpdate()) {
            return $this->previous_data;
        }
        if ($this->status_verif !== 'verified') {
            return null;
        }

        // getAttributes() = nilai mentah DB, jadi tanggal tidak bergeser timezone.
        return Arr::only($this->getAttributes(), static::snapshotFields());
    }

    /** Kembalikan semua kolom ke snapshot, lalu hapus snapshot. Belum disimpan: panggil save() sesudahnya. */
    public function restoreSnapshot(): void
    {
        $snapshot = $this->previous_data ?? [];
        $this->forceFill($snapshot);
        $this->pending_action = null;
        $this->previous_data = null;
    }
}
