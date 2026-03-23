<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\BisnisUnit;

class Vehicle extends Model
{
    protected $table = 'drms_vehicles';
    protected $fillable = ['type', 'plate_number', 'capacity', 'status', 'business_unit_id'];

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
}