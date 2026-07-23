<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\BisnisUnit;

class Voucher extends Model
{
    protected $table = 'drms_vouchers'; // perbaiki jika masih 'vouchers'
    protected $fillable = ['code', 'nominal', 'type', 'status', 'business_unit_id', 'input_business_unit_id'];

    protected $casts = [
        'type' => 'string',
        'status' => 'string',
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'business_unit_id', 'id_bisnis_unit');
    }

    /**
     * Business unit tujuan/input tambahan yang dipilih saat membuat voucher.
     * Saat ini khusus dipakai oleh user dari business unit "KPN Corporation".
     */
    public function inputBusinessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'input_business_unit_id', 'id_bisnis_unit');
    }

    public function request()
    {
        return $this->hasOne(DriverRequest::class);
    }
}