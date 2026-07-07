<?php

namespace App\Models\HSRM;

use Illuminate\Database\Eloquent\Model;

class HsrmCertificateType extends Model
{
    protected $table = 'hsrm_certificate_types';

    protected $fillable = ['name', 'description'];

    public function certificates()
    {
        return $this->hasMany(HsrmCertificate::class, 'certificate_type_id');
    }
}