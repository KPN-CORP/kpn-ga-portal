<?php

namespace App\Models\budget_m;

use Illuminate\Database\Eloquent\Model;

class BudgetSub extends Model
{
    protected $table = 'budget_sub';
    protected $fillable = ['kategori_id', 'nama', 'urut', 'aktif'];
    protected $casts = ['aktif' => 'boolean'];

    public function kategori()
    {
        return $this->belongsTo(BudgetKategori::class, 'kategori_id');
    }

    public function items()
    {
        return $this->hasMany(BudgetItem::class, 'sub_id');
    }
}
