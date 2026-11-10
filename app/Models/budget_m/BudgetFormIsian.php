<?php

namespace App\Models\budget_m;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Satu isian form = satu invoice = tepat satu baris budget_tagihan (lewat budget_tagihan.isian_id). */
class BudgetFormIsian extends Model
{
    protected $table = 'budget_form_isian';
    protected $fillable = ['form_id', 'tanggal', 'no_dokumen', 'dpp', 'ppn_persen', 'ppn', 'materai', 'total', 'data', 'input_oleh'];
    protected $casts = ['tanggal' => 'date', 'data' => 'array'];

    public function form()
    {
        return $this->belongsTo(BudgetForm::class, 'form_id');
    }

    public function tagihan()
    {
        return $this->hasOne(BudgetTagihan::class, 'isian_id');
    }

    public function penginput()
    {
        return $this->belongsTo(User::class, 'input_oleh');
    }
}
