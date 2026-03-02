<?php

namespace App\Models\Founddesk;

use Illuminate\Database\Eloquent\Model;

class FounddeskAccess extends Model
{
    protected $table = 'tb_access_menu';
    public $timestamps = false;

    public static function hasAccess(string $username): bool
    {
        return self::where('username', $username)
            ->where('founddesk_access', 1)
            ->exists();
    }
}