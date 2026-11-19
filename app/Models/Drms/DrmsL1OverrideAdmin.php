<?php

namespace App\Models\Drms;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin DRMS yang boleh approve/tolak L1 atas nama atasan (diisi lewat database).
 * Cakupan request tetap mengikuti BU/area admin tersebut.
 */
class DrmsL1OverrideAdmin extends Model
{
    protected $table = 'drms_l1_override_admins';

    protected $fillable = ['user_id', 'note'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
