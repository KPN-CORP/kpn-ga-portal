<?php

namespace App\Models\HSRM;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\AreaKerja;

class HsrmUserRole extends Model
{
    protected $table = 'hsrm_user_roles';

    protected $fillable = [
        'user_id',
        'area_id',
        'role',
        'can_approve',
        'created_at',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function area()
    {
        return $this->belongsTo(AreaKerja::class, 'area_id', 'id_area_kerja');
    }
}