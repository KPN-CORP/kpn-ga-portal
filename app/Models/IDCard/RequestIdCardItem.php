<?php

namespace App\Models\IDCard;

use Illuminate\Database\Eloquent\Model;

class RequestIdCardItem extends Model
{
    protected $table = 'request_idcard_item';

    public $timestamps = false;

    protected $fillable = ['request_id', 'item_id', 'qty', 'created_at'];

    protected $casts = ['qty' => 'integer', 'created_at' => 'datetime'];

    public function item()
    {
        return $this->belongsTo(IdCardItem::class, 'item_id');
    }

    public function request()
    {
        return $this->belongsTo(RequestIdCard::class, 'request_id');
    }
}
