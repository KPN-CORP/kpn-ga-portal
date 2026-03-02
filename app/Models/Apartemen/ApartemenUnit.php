<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApartemenUnit extends Model
{
    use SoftDeletes;

    protected $table = 'tb_apartemen_unit';
    protected $fillable = [
        'apartemen_id',
        'nomor_unit',
        'kapasitas',
        'status',
        'catatan'
    ];

    protected $casts = [
        'kapasitas' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    // Relasi ke apartemen
    public function apartemen()
    {
        return $this->belongsTo(Apartemen::class, 'apartemen_id');
    }

    // Relasi ke assigns (assignment/penempatan)
    public function assigns()
    {
        return $this->hasMany(ApartemenAssign::class, 'unit_id')
            ->whereNull('deleted_at');
    }

    // Relasi ke assign aktif
    public function activeAssigns()
    {
        return $this->hasMany(ApartemenAssign::class, 'unit_id')
            ->where('status', 'AKTIF')
            ->whereNull('deleted_at');
    }

    // Relasi ke aset unit
    public function unitAsets()
    {
        return $this->hasMany(ApartemenUnitAset::class, 'unit_id')
            ->whereNull('deleted_at');
    }

    // Relasi ke aset melalui unit_asets
    public function asets()
    {
        return $this->belongsToMany(ApartemenAset::class, 'tb_apartemen_unit_aset', 'unit_id', 'aset_id')
            ->withPivot('jumlah', 'kondisi', 'catatan')
            ->whereNull('tb_apartemen_unit_aset.deleted_at')
            ->whereNull('tb_apartemen_aset.deleted_at');
    }

    // Scope untuk unit tersedia
    public function scopeReady($query)
    {
        return $query->where('status', 'READY');
    }

    // Scope untuk unit terisi
    public function scopeTerisi($query)
    {
        return $query->where('status', 'TERISI');
    }

    // Scope untuk unit maintenance
    public function scopeMaintenance($query)
    {
        return $query->where('status', 'MAINTENANCE');
    }

    // Scope untuk unit aktif (tidak dihapus)
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    // Accessor untuk status
    public function getStatusLabelAttribute()
    {
        $statuses = [
            'READY' => 'Tersedia',
            'TERISI' => 'Terisi',
            'MAINTENANCE' => 'Maintenance'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    // Accessor untuk status warna
    public function getStatusColorAttribute()
    {
        $colors = [
            'READY' => 'success',
            'TERISI' => 'primary',
            'MAINTENANCE' => 'warning'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    // Hitung jumlah penghuni aktif
    public function getJumlahPenghuniAktifAttribute()
    {
        return $this->activeAssigns()->count();
    }

    // Cek apakah unit tersedia
    public function getIsAvailableAttribute()
    {
        return $this->status === 'READY' && $this->jumlah_penghuni_aktif === 0;
    }

    // Cek apakah unit bisa dihapus
    public function getCanDeleteAttribute()
    {
        return $this->status === 'READY' && 
               $this->activeAssigns()->count() === 0 &&
               $this->deleted_at === null;
    }
}