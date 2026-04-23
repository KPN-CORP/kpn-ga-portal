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

    // Relasi tidak diubah, hanya ditampilkan untuk kelengkapan
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
     * Cek tumpang tindih untuk driver/kendaraan pada rentang waktu multi-hari.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column         Nama kolom (driver_id / vehicle_id)
     * @param int    $id             ID driver/kendaraan
     * @param string $startDate      Y-m-d
     * @param string $startTime      H:i:s
     * @param string $endDate        Y-m-d
     * @param string $endTime        H:i:s
     * @param int|null $excludeId    ID request yang dikecualikan (saat update)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverlappingPeriod($query, $column, $id, $startDate, $startTime, $endDate, $endTime, $excludeId = null)
    {
        if (!$startTime || !$endTime) {
            return $query->whereRaw('1 = 0'); // tidak mungkin ada konflik jika waktu tidak lengkap
        }

        $query->where($column, $id)
              ->whereIn('status', ['approved_admin', 'pending_l1', 'approved_l1'])
              ->where(function ($q) use ($startDate, $startTime, $endDate, $endTime) {
                  // Gabungkan tanggal & waktu menjadi datetime untuk perbandingan yang lebih akurat
                  $start = Carbon::parse($startDate . ' ' . $startTime);
                  $end   = Carbon::parse($endDate . ' ' . $endTime);

                  $q->where(function ($sub) use ($start, $end) {
                      // 1. Request yang usage_date berada di dalam rentang [start, end]
                      $sub->whereBetween('usage_date', [$start->toDateString(), $end->toDateString()])
                          // 2. Request dengan return_date berada di dalam rentang (jika ada)
                          ->orWhere(function ($q2) use ($start, $end) {
                              $q2->whereNotNull('return_date')
                                 ->whereBetween('return_date', [$start->toDateString(), $end->toDateString()]);
                          })
                          // 3. Request yang melingkupi seluruh rentang (usage_date <= start AND return_date >= end)
                          ->orWhere(function ($q3) use ($start, $end) {
                              $q3->where('usage_date', '<=', $start->toDateString())
                                 ->where(function ($q4) use ($end) {
                                     $q4->where('return_date', '>=', $end->toDateString())
                                        ->orWhereNull('return_date'); // jika return_date null, artinya masih berlangsung
                                 });
                          });
                  });

                  // Tambahan: periksa tumpang tindih pada hari yang sama dengan waktu yang tepat
                  $q->orWhere(function ($qTime) use ($start, $end) {
                      $qTime->where('usage_date', $start->toDateString())
                            ->whereTime('start_time', '<', $end->toTimeString())
                            ->whereTime('end_time', '>', $start->toTimeString());
                  });
              });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query;
    }

    /**
     * Wrapper untuk pengecekan tumpang tindih pada satu hari.
     */
    public function scopeOverlapping($query, $column, $id, $date, $start, $end, $excludeId = null)
    {
        return $this->scopeOverlappingPeriod($query, $column, $id, $date, $start, $date, $end, $excludeId);
    }

    public function scopeDriverAvailableOnDate($query, $driverId, $date, $excludeId = null)
    {
        return $this->scopeOverlapping($query, 'driver_id', $driverId, $date, '00:00:00', '23:59:59', $excludeId);
    }

    public function scopeVehicleAvailableOnDate($query, $vehicleId, $date, $excludeId = null)
    {
        return $this->scopeOverlapping($query, 'vehicle_id', $vehicleId, $date, '00:00:00', '23:59:59', $excludeId);
    }
}