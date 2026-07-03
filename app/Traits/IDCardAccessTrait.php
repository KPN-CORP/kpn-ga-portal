<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait IDCardAccessTrait
{
    protected function isSuperAdmin()
    {
        $user = Auth::user();
        return $user->username == 'admin' ||
               DB::table('tb_access_menu')
                   ->where('username', $user->username)
                   ->where('proses_idcard', 1)
                   ->exists();
    }

    protected function getAdminBUAccess()
    {
        return DB::table('request_idcard_accesbu')
            ->where('user_id', Auth::id())
            ->pluck('bisnis_unit_id')
            ->toArray();
    }

    protected function canProses()
    {
        return $this->isSuperAdmin() || !empty($this->getAdminBUAccess());
    }

    protected function applyAccessFilter($query)
    {
        $user = Auth::user();
        if ($this->isSuperAdmin()) {
            return $query;
        }

        $adminBU = $this->getAdminBUAccess();
        if (!empty($adminBU)) {
            return $query->whereIn('bisnis_unit_id', $adminBU);
        } else {
            return $query->where('user_id', $user->id);
        }
    }

    protected function canAccessRequest($requestId)
    {
        $user = Auth::user();
        if ($this->isSuperAdmin()) {
            return true;
        }

        $adminBU = $this->getAdminBUAccess();
        if (!empty($adminBU)) {
            $request = DB::table('request_idcard')
                ->where('id', $requestId)
                ->first();
            if ($request && in_array($request->bisnis_unit_id, $adminBU)) {
                return true;
            }
        }

        return DB::table('request_idcard')
            ->where('id', $requestId)
            ->where('user_id', $user->id)
            ->exists();
    }
}