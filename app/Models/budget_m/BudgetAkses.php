<?php

namespace App\Models\budget_m;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BudgetAkses extends Model
{
    protected $table = 'budget_akses';
    protected $fillable = ['user_id', 'peran', 'nama_dept', 'aktif'];
    protected $casts = ['aktif' => 'boolean'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}