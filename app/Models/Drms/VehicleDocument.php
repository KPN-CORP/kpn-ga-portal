<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;

class VehicleDocument extends Model
{
    protected $table = 'drms_vehicle_documents';
    protected $fillable = [
        'vehicle_id', 'stnk_expiry', 'tax_yearly_expiry',
        'tax_5year_expiry', 'insurance_expiry',
        'stnk_file', 'tax_file', 'insurance_file', 'notes'
    ];

    protected $casts = [
        'stnk_expiry' => 'date',
        'tax_yearly_expiry' => 'date',
        'tax_5year_expiry' => 'date',
        'insurance_expiry' => 'date',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}