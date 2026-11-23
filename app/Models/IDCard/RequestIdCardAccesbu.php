<?php

namespace App\Models\IDCard;

use App\Models\User;
use App\Models\BisnisUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestIdCardAccesbu extends Model
{
    use HasFactory;

    protected $table = 'request_idcard_accesbu';

    protected $fillable = [
        'user_id',
        'bisnis_unit_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bisnisUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'bisnis_unit_id', 'id_bisnis_unit');
    }
}