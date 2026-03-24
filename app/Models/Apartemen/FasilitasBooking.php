<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FasilitasBooking extends Model
{
    protected $table = 'tb_fasilitas_booking';
    protected $fillable = [
        'user_id', 'fasilitas_id', 'tanggal_booking', 'jam_mulai', 'jam_selesai',
        'jumlah_orang', 'status', 'catatan', 'approved_by', 'approved_at', 'reject_reason'
    ];

    protected $casts = [
        'tanggal_booking' => 'date',
        'jam_mulai' => 'datetime:H:i',
        'jam_selesai' => 'datetime:H:i',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function fasilitas()
    {
        return $this->belongsTo(Fasilitas::class, 'fasilitas_id');
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'PENDING'    => 'Menunggu',
            'APPROVED'   => 'Disetujui',
            'REJECTED'   => 'Ditolak',
            'CANCELLED'  => 'Dibatalkan',
            'CHECKED_IN' => 'Check‑in',
            'CHECKED_OUT'=> 'Check‑out',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'PENDING'    => 'yellow',
            'APPROVED'   => 'green',
            'REJECTED'   => 'red',
            'CANCELLED'  => 'gray',
            'CHECKED_IN' => 'blue',
            'CHECKED_OUT'=> 'indigo',
        ];
        return $colors[$this->status] ?? 'gray';
    }
}