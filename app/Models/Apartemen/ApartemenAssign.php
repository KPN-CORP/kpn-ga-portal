<?php
// app/Models/Apartemen/ApartemenAssign.php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApartemenAssign extends Model
{
    use SoftDeletes;

    protected $table = 'tb_apartemen_assign';
    
    protected $fillable = [
        'request_id',
        'unit_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'checkin_at', // TAMBAHKAN INI
        'status',
        'assign_by'
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'checkin_at' => 'datetime', // TAMBAHKAN INI
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relasi ke request
    public function request()
    {
        return $this->belongsTo(ApartemenRequest::class, 'request_id')
            ->whereNull('deleted_at');
    }

    // Relasi ke unit
    public function unit()
    {
        return $this->belongsTo(ApartemenUnit::class, 'unit_id')
            ->whereNull('deleted_at');
    }

    // Relasi ke penghuni
    public function penghuni()
    {
        return $this->hasMany(ApartemenPenghuni::class, 'assign_id')
            ->whereNull('deleted_at');
    }

    // Relasi ke penghuni aktif
    public function penghuniAktif()
    {
        return $this->hasMany(ApartemenPenghuni::class, 'assign_id')
            ->where('status', 'AKTIF')
            ->whereNull('deleted_at');
    }

    // Relasi ke apartemen melalui unit
    public function apartemen()
    {
        return $this->through('unit')->has('apartemen');
    }

    // Scope untuk assignment aktif
    public function scopeAktif($query)
    {
        return $query->where('status', 'AKTIF');
    }

    // Scope untuk assignment selesai
    public function scopeSelesai($query)
    {
        return $query->where('status', 'SELESAI');
    }

    // Accessor untuk periode
    public function getPeriodeAttribute()
    {
        return $this->tanggal_mulai->format('d/m/Y') . ' - ' . $this->tanggal_selesai->format('d/m/Y');
    }

    // Method untuk mendapatkan jumlah penghuni aktif
    public function getActivePenghuniCountAttribute()
    {
        return $this->penghuni()->where('status', 'AKTIF')->count();
    }

    // Method untuk cek apakah assignment sudah kosong
    public function getIsEmptyAttribute()
    {
        return $this->active_penghuni_count == 0;
    }

    // Accessor untuk status
    public function getStatusLabelAttribute()
    {
        $statuses = [
            'AKTIF' => 'Aktif',
            'SELESAI' => 'Selesai'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    // Cek apakah sudah melewati tanggal selesai
    public function getIsOverdueAttribute()
    {
        return now()->greaterThan($this->tanggal_selesai);
    }

    // Hitung sisa hari
    public function getSisaHariAttribute()
    {
        return now()->diffInDays($this->tanggal_selesai, false);
    }
    
    // Cek apakah sudah check-in
    public function getSudahCheckinAttribute()
    {
        return !is_null($this->checkin_at);
    }
}