<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Memos\Memos;

class MemosPolicy
{
    public function viewAny(User $user)
    {
        return $user->isMemoUser() || $user->isMemoAdmin() || $user->isMemoSuperadmin();
    }

    public function view(User $user, Memos $memo)
    {
        if ($user->isMemoSuperadmin()) return true;
        if ($user->isMemoAdmin()) return $memo->business_unit === $user->getBusinessUnitAttribute();
        if ($user->isMemoUser()) return $memo->created_by === $user->id;
        return false;
    }

    public function create(User $user)
    {
        return $user->isMemoUser() || $user->isMemoAdmin() || $user->isMemoSuperadmin();
    }

    public function update(User $user, Memos $memo)
    {
        if ($user->isMemoSuperadmin()) return true;
        if ($user->isMemoAdmin()) return $memo->business_unit === $user->getBusinessUnitAttribute() && $memo->status === 'draft';
        return $user->id === $memo->created_by && $memo->status === 'draft';
    }

    public function delete(User $user, Memos $memo)
    {
        if ($user->isMemoSuperadmin()) return true;
        if ($user->isMemoAdmin()) return $memo->business_unit === $user->getBusinessUnitAttribute() && $memo->status === 'draft';
        return $user->id === $memo->created_by && $memo->status === 'draft';
    }
}