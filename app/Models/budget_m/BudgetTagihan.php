<?php

namespace App\Models\budget_m;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class BudgetTagihan extends Model
{
    protected $table = 'budget_tagihan';
    protected $fillable = ['item_id', 'isian_id', 'tahun', 'bulan', 'tagihan', 'ppn', 'biaya_materai', 'total', 'keterangan', 'input_oleh'];

    public function item()
    {
        return $this->belongsTo(BudgetItem::class, 'item_id');
    }

    /** Isian form asal baris ini (null = input manual). Baris dari form tidak boleh diubah dari halaman Realisasi. */
    public function isian()
    {
        return $this->belongsTo(BudgetFormIsian::class, 'isian_id');
    }

    public function penginput()
    {
        return $this->belongsTo(User::class, 'input_oleh');
    }
}
