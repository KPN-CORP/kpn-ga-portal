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

    // Sumber tunggal label (dipakai view, export, dan accessor di bawah)
    public const KATEGORI_LABELS = [
        'karyawan_baru'    => 'Karyawan Baru',
        'karyawan_mutasi'  => 'Karyawan Mutasi',
        'ganti_kartu'      => 'Ganti Kartu',
        'magang'           => 'Magang',
        'magang_extend'    => 'Magang Extend',
        'perubahan_lantai' => 'Perubahan Lantai',
    ];

    public const STATUS_LABELS = [
        'pending'  => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    // Kategori yang kartunya tidak punya masa berlaku (kartu tetap)
    public const KATEGORI_TETAP = ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu', 'perubahan_lantai'];

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
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getKategoriTextAttribute()
    {
        return self::KATEGORI_LABELS[$this->kategori] ?? $this->kategori;
    }

    /**
     * Informasi periode masa berlaku untuk badge di tampilan.
     * Dibandingkan per TANGGAL (bukan jam), sehingga di hari terakhir
     * kartu masih dihitung aktif ("Berakhir hari ini").
     *
     * @return array{state:string,label:string,class:string,range:string,days:int}|null
     */
    public function getPeriodeInfoAttribute(): ?array
    {
        // Kartu tetap (tanpa masa berlaku): periode dibaca dari Tanggal Join
        if (!$this->masa_berlaku || !$this->sampai_tanggal) {
            if (!$this->tanggal_join) {
                return null;
            }

            $today = now()->startOfDay();
            $join  = $this->tanggal_join->copy()->startOfDay();
            $range = 'Join: ' . $join->format('d-m-Y');
            $days  = (int) $today->diffInDays($join, false); // positif = join masih akan datang

            if ($days > 0) {
                return ['state' => 'belum', 'label' => 'Belum mulai (join ' . $days . ' hari lagi)', 'class' => 'bg-blue-100 text-blue-800', 'range' => $range, 'days' => $days];
            }
            return ['state' => 'tetap', 'label' => 'Tetap', 'class' => 'bg-gray-100 text-gray-700', 'range' => $range, 'days' => $days];
        }

        $today   = now()->startOfDay();
        $mulai   = $this->masa_berlaku->copy()->startOfDay();
        $selesai = $this->sampai_tanggal->copy()->startOfDay();
        $range   = $mulai->format('d-m-Y') . ' - ' . $selesai->format('d-m-Y');
        $days    = (int) $today->diffInDays($selesai, false); // negatif = sudah lewat

        if ($today->lt($mulai)) {
            return ['state' => 'belum', 'label' => 'Belum mulai', 'class' => 'bg-blue-100 text-blue-800', 'range' => $range, 'days' => $days];
        }
        if ($days < 0) {
            return ['state' => 'lewat', 'label' => 'Tidak aktif (lewat ' . abs($days) . ' hari)', 'class' => 'bg-gray-100 text-gray-800', 'range' => $range, 'days' => $days];
        }
        if ($days === 0) {
            return ['state' => 'hari_ini', 'label' => 'Berakhir hari ini', 'class' => 'bg-red-100 text-red-800', 'range' => $range, 'days' => 0];
        }
        if ($days <= 30) {
            return ['state' => 'segera', 'label' => 'Habis ' . $days . ' hari', 'class' => 'bg-orange-100 text-orange-800', 'range' => $range, 'days' => $days];
        }
        return ['state' => 'aktif', 'label' => 'Aktif', 'class' => 'bg-green-100 text-green-800', 'range' => $range, 'days' => $days];
    }

    public function getCanProcessAttribute()
    {
        return $this->status === 'pending';
    }
}