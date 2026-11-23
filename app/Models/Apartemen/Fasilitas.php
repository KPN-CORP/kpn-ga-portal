<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;

class Fasilitas extends Model
{
    protected $table = 'tb_fasilitas';
    protected $fillable = ['nama_fasilitas', 'deskripsi', 'kapasitas', 'jam_operasional', 'is_active'];

    public function bookings()
    {
        return $this->hasMany(FasilitasBooking::class, 'fasilitas_id');
    }

    public function activeBookings()
    {
        return $this->hasMany(FasilitasBooking::class, 'fasilitas_id')
            ->whereIn('status', ['APPROVED', 'CHECKED_IN']);
    }
}