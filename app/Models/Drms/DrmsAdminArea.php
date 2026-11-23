<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;

/**
 * Area yang dipegang seorang admin DRMS (satu baris per area).
 * Admin tanpa baris = lintas semua area di BU-nya.
 */
class DrmsAdminArea extends Model
{
    protected $table = 'drms_admin_areas';

    protected $fillable = ['user_id', 'area'];
}
