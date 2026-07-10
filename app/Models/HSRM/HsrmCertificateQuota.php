<?php

namespace App\Models\HSRM;

use Illuminate\Database\Eloquent\Model;
use App\Models\AreaKerja;

class HsrmCertificateQuota extends Model
{
    protected $table = 'hsrm_certificate_quotas';

    protected $fillable = [
        'area_id',
        'certificate_type_id',
        'quota',
        'budget',
        'regulatory',
        'application_type',
    ];

    public function area()
    {
        return $this->belongsTo(AreaKerja::class, 'area_id', 'id_area_kerja');
    }

    public function certificateType()
    {
        return $this->belongsTo(HsrmCertificateType::class);
    }

    // Relasi ke certificate (dengan area dan tipe yang sesuai)
    public function certificates()
    {
        return $this->hasMany(HsrmCertificate::class, 'area_id', 'area_id')
                    ->where('certificate_type_id', $this->certificate_type_id);
    }

    // Untuk mendapatkan jumlah aktif (verified & belum expired)
    public function getActiveCountAttribute()
    {
        return $this->certificates()
                    ->where('status_verif', 'verified')
                    ->where('expired_date', '>', now())
                    ->count();
    }

    public function getExpiredCountAttribute()
    {
        return $this->certificates()
                    ->where('expired_date', '<=', now())
                    ->count();
    }
}