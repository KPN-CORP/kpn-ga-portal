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
    ];

    protected $casts = [
        'usage_date'     => 'date',
        'return_date'    => 'date',
        'start_time'     => 'string',
        'end_time'       => 'string',
        'return_time'    => 'string',
        'approved_l1_at' => 'datetime',
        'approved_admin_at' => 'datetime',
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
     * Cek tumpang tindih untuk driver/kendaraan pada rentang waktu tertentu.
     *
     * @param  string  $column      'driver_id' atau 'vehicle_id'
     * @param  int     $id          ID driver/kendaraan
     * @param  string  $startDate   Y-m-d
     * @param  string  $startTime   H:i:s
     * @param  string  $endDate     Y-m-d
     * @param  string  $endTime     H:i:s
     * @param  int|null $excludeId  ID request yang dikecualikan
     */
    public function scopeOverlappingPeriod($query, $column, $id, $startDate, $startTime, $endDate, $endTime, $excludeId = null)
    {
        if (!$startTime || !$endTime) {
            return $query->whereRaw('1 = 0');
        }

        // Gabungkan tanggal + jam agar bisa dibandingkan langsung
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