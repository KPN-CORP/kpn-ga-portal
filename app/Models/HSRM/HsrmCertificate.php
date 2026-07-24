<?php

namespace App\Models\HSRM;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BisnisUnit;
use App\Models\AreaKerja;

class HsrmCertificate extends Model
{
    protected $table = 'hsrm_certificates';

    // Konstanta status verifikasi
    const STATUS_PENDING = 'pending';
    const STATUS_VERIFIED = 'verified';
    const STATUS_REVISION = 'revision';

    // Konstanta rekomendasi
    const REKOMENDASI_RECOMMENDED = 'recommended';
    const REKOMENDASI_NOT_RECOMMENDED = 'not_recommended';
    const REKOMENDASI_VALID = 'valid';

    protected $fillable = [
        'business_unit_id',
        'area_id',
        'pic_user_id',
        'employee_name',
        'nik',
        'certificate_type_id',
        'custom_certificate_type',
        'instansi_pengurusan',
        'expired_date',
        'status_verif',
        'status_kepemilikan',
        'rekomendasi',
        'notes',
        'attachment_path',
        'old_attachments',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expired_date' => 'date',
        'approved_at' => 'datetime',
        'status_kepemilikan' => 'boolean',
        'old_attachments' => 'array',
        // rekomendasi tidak di-cast karena berupa string enum
    ];

    // ========== RELATIONSHIPS ==========
    public function businessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'business_unit_id', 'id_bisnis_unit');
    }

    public function area()
    {
        return $this->belongsTo(AreaKerja::class, 'area_id', 'id_area_kerja');
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function certificateType()
    {
        return $this->belongsTo(HsrmCertificateType::class, 'certificate_type_id');
    }

    // ========== HELPER ATTRIBUTES ==========
    /**
     * Get recommendation label
     */
    public function getRekomendasiLabelAttribute()
    {
        return match ($this->rekomendasi) {
            self::REKOMENDASI_RECOMMENDED => 'Recommended',
            self::REKOMENDASI_NOT_RECOMMENDED => 'Not Recommended',
            self::REKOMENDASI_VALID => 'Valid',
            default => '-',
        };
    }

    /**
     * Get recommendation badge color class
     */
    public function getRekomendasiBadgeAttribute()
    {
        return match ($this->rekomendasi) {
            self::REKOMENDASI_RECOMMENDED => 'text-green-600',
            self::REKOMENDASI_NOT_RECOMMENDED => 'text-red-600',
            self::REKOMENDASI_VALID => 'text-blue-600',
            default => 'text-gray-400',
        };
    }

    // ========== SCOPES ==========
    /**
     * Scope untuk filter rekomendasi
     */
    public function scopeRekomendasi($query, $value)
    {
        if (in_array($value, [
            self::REKOMENDASI_RECOMMENDED,
            self::REKOMENDASI_NOT_RECOMMENDED,
            self::REKOMENDASI_VALID
        ])) {
            return $query->where('rekomendasi', $value);
        }
        return $query;
    }
}