<?php

namespace App\Policies;

use App\Models\Memos\MemoTeamAdmin;
use App\Models\Memos\Memos;
use App\Models\User;

/**
 * Aturan akses e-Memo. Logika "siapa boleh melihat" di sini HARUS sama dengan
 * Memos::scopeViewable() (daftar memo), supaya yang tampil di daftar selalu bisa dibuka.
 *
 * - Superadmin : semua memo.
 * - Admin tim  : memo miliknya + memo dari tim yang dia administer.
 * - User       : memo miliknya sendiri.
 */
class MemosPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isMemoUser() || $user->isMemoAdmin() || $user->isMemoSuperadmin();
    }

    public function view(User $user, Memos $memo): bool
    {
        if ($user->isMemoSuperadmin()) {
            return true;
        }

        if ($user->isMemoAdmin()) {
            return $this->isOwner($user, $memo) || $this->administersTeamOf($user, $memo);
        }

        if ($user->isMemoUser()) {
            return $this->isOwner($user, $memo);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->isMemoUser() || $user->isMemoAdmin() || $user->isMemoSuperadmin();
    }

    /**
     * Edit draft atau revisi memo yang sudah submitted (nomor memo tetap, versi lama
     * otomatis dibackup). Memo dengan status lain tidak bisa diubah, kecuali superadmin.
     */
    public function update(User $user, Memos $memo): bool
    {
        if ($user->isMemoSuperadmin()) {
            return true;
        }

        return $this->view($user, $memo)
            && in_array($memo->status, Memos::EDITABLE_STATUSES, true);
    }

    /**
     * Hanya draft yang boleh dihapus (superadmin lolos di policy, tapi controller
     * tetap membatasi penghapusan ke draft).
     */
    public function delete(User $user, Memos $memo): bool
    {
        if ($user->isMemoSuperadmin()) {
            return true;
        }

        return $this->view($user, $memo) && $memo->status === 'draft';
    }

    private function isOwner(User $user, Memos $memo): bool
    {
        return (int) $memo->created_by === (int) $user->id;
    }

    private function administersTeamOf(User $user, Memos $memo): bool
    {
        return $memo->team_id !== null
            && MemoTeamAdmin::where('user_id', $user->id)->where('team_id', $memo->team_id)->exists();
    }
}
