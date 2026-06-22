<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Carbon\Carbon;

class DriverRequest extends Model
{
    protected $table = 'drms_requests';
    
    protected $fillable = [
        'request_no', 'requester_id', 'approver_l1_id', 'admin_id',
        'driver_id', 'vehicle_id', 'voucher_id',
        'usage_date', 'start_time', 'end_time', 'pickup_location', 'destination', 'purpose',
        'transport_type', 'status', 'rejection_reason',
        'approved_l1_at', 'approved_admin_at',
        'trip_type', 'return_date', 'return_time',
        'pickup_maps_link', 'destination_maps_link',
        // Kolom baru untuk forward ke BU lain
        'current_business_unit_id', 'original_business_unit_id',
        'forwarded_by_user_id', 'forwarded_at',
    ];

    protected $casts = [
        'usage_date'     => 'date',
        'return_date'    => 'date',
        'start_time'     => 'string',
        'end_time'       => 'string',
        'return_time'    => 'string',
        'approved_l1_at' => 'datetime',
        'approved_admin_at' => 'datetime',
        'forwarded_at'   => 'datetime',
    ];

    // Relasi
    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approverL1()
    {
        return $this->belongsTo(User::class, 'approver_l1_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    /**
     * Relasi ke business unit yang sedang menangani request (jika di-forward)
     */
    public function currentBusinessUnit()
    {
        return $this->belongsTo(\App\Models\BisnisUnit::class, 'current_business_unit_id', 'id_bisnis_unit');
    }

    /**
     * Relasi ke business unit asal request (dari pemohon)
     */
    public function originalBusinessUnit()
    {
        return $this->belongsTo(\App\Models\BisnisUnit::class, 'original_business_unit_id', 'id_bisnis_unit');
    }

    /**
     * Relasi ke user yang melakukan forward
     */
    public function forwardedBy()
    {
        return $this->belongsTo(User::class, 'forwarded_by_user_id');
    }

    // Tambahkan di dalam model DriverRequest
    public function tripLog()
    {
        return $this->hasOne(TripLog::class, 'request_id');
    }

    /**
     * Scope untuk menampilkan request yang menunggu approval admin
     * berdasarkan business unit dan area, dengan mempertimbangkan current_business_unit_id.
     */
    public function scopePendingForAdmin($query, $businessUnitId, $area = null)
    {
        $query->where('status', 'approved_l1')
              ->where(function ($q) use ($businessUnitId, $area) {
                  // 1. Jika current_business_unit_id terisi, gunakan itu
                  $q->where('current_business_unit_id', $businessUnitId)
                    // 2. Jika belum di-forward, gunakan BU requester
                    ->orWhere(function ($sub) use ($businessUnitId, $area) {
                        $sub->whereNull('current_business_unit_id')
                            ->whereHas('requester.drmsProfile', function ($q2) use ($businessUnitId, $area) {
                                $q2->where('business_unit_id', $businessUnitId);
                                if ($area) {
                                    $q2->where('area', $area);
                                }
                            });
                    });
              });
    }

    /**
     * Scope untuk menampilkan request yang menunggu approval admin
     * (versi lama, hanya berdasarkan BU requester). Masih dipertahankan untuk kompatibilitas.
     */
    public function scopePendingAdmin($query, $businessUnitId, $area = null)
    {
        $query->where('status', 'approved_l1')
              ->whereHas('requester', function ($q) use ($businessUnitId, $area) {
                  $q->whereHas('drmsProfile', function ($q2) use ($businessUnitId, $area) {
                      $q2->where('business_unit_id', $businessUnitId);
                      if ($area) {
                          $q2->where('area', $area);
                      }
                  });
              });
    }

    /**
     * Cek tumpang tindih (overlap) untuk driver atau kendaraan pada rentang waktu tertentu.
     * Digunakan saat admin akan menyetujui request untuk memastikan tidak ada double booking.
     *
     * @param  string  $column      'driver_id' atau 'vehicle_id'
     * @param  int     $id          ID driver/kendaraan
     * @param  string  $startDate   Y-m-d
     * @param  string  $startTime   H:i:s
     * @param  string  $endDate     Y-m-d
     * @param  string  $endTime     H:i:s
     * @param  int|null $excludeId  ID request yang dikecualikan (untuk edit)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverlappingPeriod($query, $column, $id, $startDate, $startTime, $endDate, $endTime, $excludeId = null)
    {
        if (!$startTime || !$endTime) {
            return $query->whereRaw('1 = 0');
        }

        $start = $startDate . ' ' . $startTime;
        $end   = $endDate . ' ' . $endTime;

        return $query->where($column, $id)
            ->whereIn('status', ['approved_admin', 'pending_l1', 'approved_l1'])
            ->whereRaw("
                CONCAT(usage_date, ' ', start_time) < ?
                AND CONCAT(COALESCE(return_date, usage_date), ' ', COALESCE(return_time, end_time)) > ?
            ", [$end, $start])
            ->when($excludeId, function ($q) use ($excludeId) {
                $q->where('id', '!=', $excludeId);
            });
    }
}