<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BisnisUnit;

class DrmsUserProfile extends Model
{
    protected $table = 'drms_user_profiles';

    protected $fillable = [
        'user_id',
        'business_unit_id',
        'unit',
        'area',
        'approver_user_id',
        'is_approver',
        'is_drms_user',
        'is_drms_admin',
    ];

    protected $casts = [
        'is_approver' => 'boolean',
        'is_drms_user' => 'boolean',
        'is_drms_admin' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function businessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'business_unit_id', 'id_bisnis_unit');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id');
    }
}