<?php

namespace App\Models\budget_m;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BudgetTagihan extends Model
{
    protected $table = 'budget_tagihan';
    protected $fillable = ['item_id', 'tahun', 'bulan', 'tagihan', 'ppn', 'biaya_materai', 'total', 'keterangan', 'input_oleh'];

    public function item()
    {
        return $this->belongsTo(BudgetItem::class, 'item_id');
    }

    public function penginput()
    {
        return $this->belongsTo(User::class, 'input_oleh');
    }
}
