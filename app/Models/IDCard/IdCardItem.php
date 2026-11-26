<?php

namespace App\Models\IDCard;

use Illuminate\Database\Eloquent\Model;

class IdCardItem extends Model
{
    protected $table = 'idcard_item';

    protected $fillable = ['kode', 'nama', 'urutan', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function logs()
    {
        return $this->hasMany(IdCardStokLog::class, 'item_id');
    }
}
