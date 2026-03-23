<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class DriverRequest extends Model
{
    protected $table = 'drms_requests';
    protected $fillable = [
        'request_no', 'requester_id', 'approver_l1_id', 'admin_id',
        'driver_id', 'vehicle_id', 'voucher_id',
        'usage_date', 'start_time', 'end_time', 'pickup_location', 'destination', 'purpose',
        'transport_type', 'status', 'rejection_reason',
        'approved_l1_at', 'approved_admin_at'
    ];

    protected $casts = [
        'usage_date' => 'date',
        'start_time' => 'string',
        'end_time'   => 'string',
        'approved_l1_at' => 'datetime',
        'approved_admin_at' => 'datetime',
    ];

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
     * Scope untuk request yang perlu diproses admin (sudah disetujui atasan).
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
     * Cek double booking untuk driver atau kendaraan berdasarkan rentang waktu.
     *
     * @param  string $column  Nama kolom ('driver_id' atau 'vehicle_id')
     * @param  mixed  $id      ID driver atau kendaraan
     * @param  string $date    Tanggal penggunaan (Y-m-d)
     * @param  string $start   Waktu mulai (H:i:s)
     * @param  string $end     Waktu selesai (H:i:s)
     * @param  int    $excludeId ID request yang dikecualikan (opsional)
     */
    public function scopeOverlapping($query, $column, $id, $date, $start, $end, $excludeId = null)
    {
        if (!$start || !$end) {
            return $query->whereRaw('1 = 0');
        }

        $query->where($column, $id)
              ->where('usage_date', $date)
              ->whereIn('status', ['approved_admin', 'pending_l1', 'approved_l1'])
              ->where(function ($q) use ($start, $end) {
                  // Gunakan whereTime untuk kolom waktu
                  $q->whereTime('start_time', '<', $end)
                    ->whereTime('end_time', '>', $start);
              });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query;
    }

    /**
     * Scope lama untuk kompatibilitas (cek driver berdasarkan tanggal saja).
     * @deprecated Gunakan scopeOverlapping untuk cek berdasarkan waktu.
     */
    public function scopeDriverAvailableOnDate($query, $driverId, $date, $excludeId = null)
    {
        $query->where('driver_id', $driverId)
              ->where('usage_date', $date)
              ->whereIn('status', ['approved_admin', 'pending_l1', 'approved_l1']);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query;
    }

    /**
     * Scope lama untuk kompatibilitas (cek kendaraan berdasarkan tanggal saja).
     * @deprecated Gunakan scopeOverlapping untuk cek berdasarkan waktu.
     */
    public function scopeVehicleAvailableOnDate($query, $vehicleId, $date, $excludeId = null)
    {
        $query->where('vehicle_id', $vehicleId)
              ->where('usage_date', $date)
              ->whereIn('status', ['approved_admin', 'pending_l1', 'approved_l1']);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        return $query;
    }
}