<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Messkar\MesBooking;

class BisnisUnit extends Model
{
    protected $table = 'tb_bisnis_unit';
    protected $primaryKey = 'id_bisnis_unit';
    public $timestamps = false;

    protected $fillable = [
        'nama_bisnis_unit'
    ];
    
    // Relationship ke Mess Booking
    public function messBookings()
    {
        return $this->hasMany(MesBooking::class, 'id_bisnis_unit', 'id_bisnis_unit');
    }
}