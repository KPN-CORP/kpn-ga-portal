<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\BisnisUnit;

class Driver extends Model
{
    protected $table = 'drms_drivers';
    protected $fillable = ['name', 'phone', 'username', 'status', 'business_unit_id'];

    protected $casts = [
        'status' => 'string',
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'business_unit_id', 'id_bisnis_unit');
    }

    public function requests()
    {
        return $this->hasMany(DriverRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'username', 'username');
    }
}