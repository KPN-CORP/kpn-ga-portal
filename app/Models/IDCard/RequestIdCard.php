<?php

namespace App\Models\IDCard;

use App\Models\User;
use App\Models\BisnisUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestIdCard extends Model
{
    use HasFactory;

    protected $table = 'request_idcard';

    protected $fillable = [
        'nik',
        'nama',
        'kategori',
        'bisnis_unit_id',
        'tanggal_join',
        'masa_berlaku',
        'sampai_tanggal',
        'nomor_kartu',
        'foto',
        'bukti_bayar',
        'keterangan',
        'status',
        'user_id',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'email_sent',
        'is_active'  // <-- tambahkan ini
    ];

    protected $casts = [
        'tanggal_join' => 'date',
        'masa_berlaku' => 'date',
        'sampai_tanggal' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public $timestamps = true;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedByUser()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function bisnisUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'bisnis_unit_id', 'id_bisnis_unit');
    }

    public function getStatusTextAttribute()
    {
        $statuses = [
            'pending'  => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak'
        ];
        return $statuses[$this->status] ?? $this->status;
    }

    public function getKategoriTextAttribute()
    {
        $labels = [
            'karyawan_baru'    => 'Karyawan Baru',
            'karyawan_mutasi'  => 'Karyawan Mutasi',
            'ganti_kartu'      => 'Ganti Kartu',
            'magang'           => 'Magang',
            'magang_extend'    => 'Magang Extend',
            'perubahan_lantai' => 'Perubahan Lantai',
        ];
        return $labels[$this->kategori] ?? $this->kategori;
    }

    public function getCanProcessAttribute()
    {
        return $this->status === 'pending';
    }
}