<?php

namespace App\Models\FinGrabm;

use Illuminate\Database\Eloquent\Model;

class FinGrabTransaction extends Model
{
    protected $table = 'fingrab_transactions';
    protected $guarded = [];
    protected $casts = [
        'trx_date' => 'date:Y-m-d',
        'distance_km' => 'float',
    ];
}
