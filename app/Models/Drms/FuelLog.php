<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FuelLog extends Model
{
    protected $table = 'drms_fuel_logs';

    protected $fillable = [
        'vehicle_id', 'driver_id', 'user_id', 'filling_date',
        'odometer_start', 'fuel_liters', 'fuel_price_per_liter', 'total_cost',
        'receipt_file', 'photo_dashboard_before', 'photo_dashboard_after',
        'is_draft',
        'is_verified', 'verified_by', 'verified_at', 'notes'
    ];

    /** Draft hanya bisa diubah/dilengkapi selama ini (dihitung dari created_at); setelahnya TERKUNCI (tidak dihapus). */
    public const DRAFT_TTL_HOURS = 6;

    protected $casts = [
        'filling_date' => 'date',
        'odometer_start' => 'integer',
        'fuel_liters' => 'decimal:2',
        'fuel_price_per_liter' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'is_verified' => 'boolean',
        'is_draft' => 'boolean',
        'verified_at' => 'datetime',
    ];

    /**
     * Log yang sudah final (bukan draft). Dipakai analitik, statistik, dan verifikasi
     * supaya draft (belum ada liter/harga/foto sesudah) tidak ikut terhitung.
     */
    public function scopeSubmitted($query)
    {
        return $query->where('is_draft', 0);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_draft', 1);
    }

    /** Draft yang sudah melewati batas DRAFT_TTL_HOURS -> terkunci. */
    public function scopeLockedDraft($query)
    {
        return $query->where('is_draft', 1)
            ->where('created_at', '<', now()->subHours(self::DRAFT_TTL_HOURS));
    }

    /** Waktu draft terkunci (created_at + 6 jam). Null untuk log final. */
    public function getDraftLocksAtAttribute()
    {
        return $this->is_draft && $this->created_at
            ? $this->created_at->copy()->addHours(self::DRAFT_TTL_HOURS)
            : null;
    }

    public function isDraftLocked(): bool
    {
        return $this->is_draft && $this->draft_locks_at && $this->draft_locks_at->isPast();
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Konsumsi dihitung dari selisih odometer antar entri
     * Tidak dihitung per entri karena tidak punya odometer_end
     */
}