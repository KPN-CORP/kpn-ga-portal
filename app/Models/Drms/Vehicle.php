<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\BisnisUnit;

class Vehicle extends Model
{
    protected $table = 'drms_vehicles';
    
    protected $fillable = [
        'type', 
        'plate_number', 
        'capacity', 
        'status', 
        'fuel_type',
        'business_unit_id',
        'gps_enabled'   // tambahan
    ];

    protected $casts = [
        'status'       => 'string',
        'gps_enabled'  => 'boolean', // cast ke boolean
    ];

    public function businessUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'business_unit_id', 'id_bisnis_unit');
    }

    /**
     * Pencarian gabungan PLAT NOMOR + MEREK (kolom `type`, mis. "BYD M6").
     *
     * Input dipecah per kata dan SEMUA kata harus cocok (di plat ATAU merek), jadi
     * "B 1929 BYD" atau "byd sdw" tetap ketemu walau urutan/format platnya tidak persis.
     * Plat juga dicocokkan tanpa spasi: "B1929SDW" cocok dengan "B 1929 SDW".
     */
    public function scopeSearchPlateBrand($query, $term)
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        foreach (preg_split('/\s+/', $term) as $word) {
            // Lewati kata yang cuma tanda baca (mis. "-" dari label rekomendasi
            // "B 1929 SDW - BYD M6") — kalau ini ikut disyaratkan cocok, hasilnya
            // jadi salah/kosong karena plat/merek jarang benar-benar mengandung "-".
            if ($word === '' || !preg_match('/[a-zA-Z0-9]/', $word)) {
                continue;
            }
            $like = '%' . addcslashes($word, '%_\\') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('plate_number', 'LIKE', $like)
                  ->orWhere('type', 'LIKE', $like)
                  ->orWhereRaw("REPLACE(plate_number, ' ', '') LIKE ?", [$like]);
            });
        }

        return $query;
    }

    public function requests()
    {
        return $this->hasMany(DriverRequest::class);
    }

    public function serviceSchedules()
    {
        return $this->hasMany(ServiceSchedule::class);
    }

    public function repairs()
    {
        return $this->hasMany(Repair::class);
    }

    public function fuelLogs()
    {
        return $this->hasMany(FuelLog::class);
    }

    public function document()
    {
        return $this->hasOne(VehicleDocument::class);
    }
}