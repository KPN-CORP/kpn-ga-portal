<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;

class ApartemenRequestPenghuni extends Model
{
    protected $table = 'tb_apartemen_request_penghuni';
    protected $fillable = [
        'request_id',
        'nama',
        'id_karyawan',
        'no_hp',
        'unit_kerja',
        'gol',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah_hari'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'jumlah_hari' => 'integer'
    ];

    public function request()
    {
        return $this->belongsTo(ApartemenRequest::class, 'request_id');
    }

    public function calculateJumlahHari()
    {
        if ($this->tanggal_mulai && $this->tanggal_selesai) {
            $start = \Carbon\Carbon::parse($this->tanggal_mulai);
            $end = \Carbon\Carbon::parse($this->tanggal_selesai);
            $this->jumlah_hari = $start->diffInDays($end) + 1;
        }
    }
}