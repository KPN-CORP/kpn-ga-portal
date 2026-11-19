<?php

namespace App\Models\budget_m;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BudgetPicHead extends Model
{
    protected $table = 'budget_pic_head';
    protected $fillable = ['pic_user_id', 'dept_head_user_id', 'aktif'];
    protected $casts = ['aktif' => 'boolean'];

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function deptHead()
    {
        return $this->belongsTo(User::class, 'dept_head_user_id');
    }
}
