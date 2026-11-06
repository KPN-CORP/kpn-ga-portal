<?php

namespace App\Models\budget_m;

use Illuminate\Database\Eloquent\Model;

/** Item yang terdaftar (tampil) di suatu tahun. */
class BudgetItemTahun extends Model
{
    protected $table = 'budget_item_tahun';
    protected $fillable = ['item_id', 'tahun'];
}
