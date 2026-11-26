<?php

namespace App\Models\IDCard;

use Illuminate\Database\Eloquent\Model;

/** Stok minimum satu item untuk satu admin. */
class IdCardStokAdmin extends Model
{
    protected $table = 'idcard_stok_admin';

    protected $fillable = ['user_id', 'item_id', 'min_stok'];

    protected $casts = ['min_stok' => 'integer'];
}
