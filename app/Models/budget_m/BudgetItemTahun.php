<?php

namespace App\Models\budget_m;

use Illuminate\Database\Eloquent\Model;

/** Item yang terdaftar di suatu tahun. aktif = item dipakai (bisa diisi) di tahun itu; nonaktif hanya berlaku untuk tahun ini. */
class BudgetItemTahun extends Model
{
    protected $table = 'budget_item_tahun';
    protected $fillable = ['item_id', 'tahun', 'aktif'];
    protected $casts = ['aktif' => 'boolean'];
}
