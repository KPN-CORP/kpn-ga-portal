<?php

namespace App\Models\budget_m;

use Illuminate\Database\Eloquent\Model;

class BudgetKategori extends Model
{
    protected $table = 'budget_kategori';
    protected $fillable = ['nama', 'urut', 'aktif'];
    protected $casts = ['aktif' => 'boolean'];

    public function subs()
    {
        return $this->hasMany(BudgetSub::class, 'kategori_id')->orderBy('urut')->orderBy('nama');
    }
}
