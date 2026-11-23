<?php

namespace App\Models\budget_m;

use Illuminate\Database\Eloquent\Model;

class BudgetBulan extends Model
{
    protected $table = 'budget_bulan';
    protected $fillable = ['item_id', 'tahun', 'bulan', 'nominal'];

    public function item()
    {
        return $this->belongsTo(BudgetItem::class, 'item_id');
    }
}
