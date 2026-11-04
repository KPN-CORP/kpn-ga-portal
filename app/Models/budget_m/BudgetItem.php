<?php

namespace App\Models\budget_m;

use App\Models\User;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model
{
    protected $table = 'budget_item';
    protected $fillable = ['sub_id', 'nama', 'bu', 'pt', 'dept_head_user_id', 'pic_user_id', 'sumber', 'keterangan', 'aktif'];
    protected $casts = ['aktif' => 'boolean'];

    public function sub()
    {
        return $this->belongsTo(BudgetSub::class, 'sub_id');
    }

    public function deptHead()
    {
        return $this->belongsTo(User::class, 'dept_head_user_id');
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function bulan()
    {
        return $this->hasMany(BudgetBulan::class, 'item_id');
    }

    public function tagihan()
    {
        return $this->hasMany(BudgetTagihan::class, 'item_id');
    }

    /** Admin: semua item. Dept Head: item miliknya. PIC: item yang ditunjuk ke dia. Lainnya: kosong. */
    public function scopeTerlihatOleh($q, $user)
    {
        $peran = BudgetPeran::peran($user);
        if ($peran === BudgetPeran::ADMIN) {
            return $q;
        }
        if ($peran === BudgetPeran::DEPT_HEAD) {
            return $q->where('dept_head_user_id', $user->id);
        }
        if ($peran === BudgetPeran::PIC) {
            return $q->where('pic_user_id', $user->id);
        }
        return $q->whereRaw('1 = 0');
    }
}
