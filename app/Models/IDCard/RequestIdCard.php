<?php

namespace App\Models\IDCard;

use App\Models\User;
use App\Models\BisnisUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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
        'visitor'          => 'Visitor',
    ];

    // Kategori lama (data historis). Tidak muncul lagi di pilihan form/filter.
    public const KATEGORI_LEGACY = [
        'perubahan_lantai' => 'Perubahan Lantai (lama)',
    ];

    // Kategori yang punya masa aktif (Masa Berlaku s/d Sampai Tanggal) + nomor kartu
    public const KATEGORI_PERIODE = ['magang', 'magang_extend', 'visitor'];

    public const STATUS_LABELS = [
        'pending'  => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];

    // Kategori yang kartunya tidak punya masa berlaku (kartu tetap)
    public const KATEGORI_TETAP = ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu', 'perubahan_lantai'];  // perubahan_lantai = data lama

    // Kartu fisik yang harus dikembalikan ke GA (magang & visitor)
    public const KATEGORI_KEMBALI = ['magang', 'magang_extend', 'visitor'];

    public const RETURN_STATUS_LABELS = [
        'none'      => 'Belum dikembalikan',
        'requested' => 'Menunggu konfirmasi GA',
        'returned'  => 'Sudah dikembalikan',
        'rejected'  => 'Pengembalian ditolak',
    ];

    public const RETURN_KONDISI_LABELS = [
        'baik'   => 'Baik',
        'rusak'  => 'Rusak',
        'hilang' => 'Hilang',
    ];

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
        'is_active',
        'result_email_sent',
        'return_status',
        'return_kondisi',
        'return_note',
        'return_requested_by',
        'return_requested_at',
        'return_processed_by',
        'return_processed_at',
        'return_reject_reason',
        'return_email_sent',
        'return_result_email_sent',
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
        'return_requested_at' => 'datetime',
        'return_processed_at' => 'datetime',
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
        return self::KATEGORI_LABELS[$this->kategori] ?? self::KATEGORI_LEGACY[$this->kategori] ?? $this->kategori;
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

    /**
     * Visitor yang masih aktif: sudah disetujui, is_active = 1, dan belum lewat
     * Sampai Tanggal (hari terakhir masih dihitung aktif).
     */
    public function getIsVisitorAktifAttribute(): bool
    {
        return $this->kategori === 'visitor'
            && $this->status === 'approved'
            && (bool) $this->is_active
            && $this->sampai_tanggal
            && !now()->startOfDay()->gt($this->sampai_tanggal->copy()->startOfDay());
    }

    public function getCanProcessAttribute()
    {
        return $this->status === 'pending';
    }

    /**
     * Admin boleh mengubah lantai (keterangan) kartu yang sudah disetujui, masih aktif,
     * dan belum lewat masa aktif. Kartu tetap (tanpa Sampai Tanggal) dianggap selalu bisa.
     * (Dipakai DetailController; sebelumnya accessor ini tidak ada sehingga selalu false.)
     */
    public function getCanEditLantaiAttribute(): bool
    {
        if ($this->status !== 'approved' || !$this->is_active) {
            return false;
        }
        if (!$this->sampai_tanggal) {
            return true;
        }
        return !now()->startOfDay()->gt($this->sampai_tanggal->copy()->startOfDay());
    }

    /**
     * Kartu ini boleh diajukan pengembalian (kategori magang/visitor, approved, aktif,
     * dan belum ada pengajuan yang sedang menunggu / sudah selesai).
     * Pemeriksaan kepemilikan (user_id) dilakukan di controller / view.
     */
    public function getCanReturnAttribute(): bool
    {
        return in_array($this->kategori, self::KATEGORI_KEMBALI, true)
            && $this->status === 'approved'
            && (bool) $this->is_active
            && in_array($this->return_status ?? 'none', ['none', 'rejected'], true);
    }

    public function getReturnStatusTextAttribute(): string
    {
        return self::RETURN_STATUS_LABELS[$this->return_status ?? 'none'] ?? (string) $this->return_status;
    }

    /**
     * Hanya kartu "terkini": sembunyikan baris lama yang sudah punya baris pengganti
     * (extend) dengan nomor kartu sama, masih aktif, dan masa berlaku lebih panjang.
     * Aturannya sama dengan NOT EXISTS pada cron reminder.
     */
    public function scopeTerkini($query)
    {
        return $query->whereNotExists(function ($sub) {
            $sub->select(DB::raw(1))
                ->from('request_idcard as n')
                ->whereColumn('n.nomor_kartu', 'request_idcard.nomor_kartu')
                ->whereColumn('n.id', '<>', 'request_idcard.id')
                ->where('n.status', 'approved')
                ->where('n.is_active', 1)
                ->whereIn('n.kategori', self::KATEGORI_PERIODE)
                ->whereColumn('n.sampai_tanggal', '>', 'request_idcard.sampai_tanggal');
        });
    }

    /**
     * Filter periode (satu tempat, dipakai halaman Semua / Aktif / Inaktif).
     * Nilai: masa_aktif | masa_tidak_aktif | masa_habis_segera | all
     */
    public function scopeFilterPeriode($query, ?string $periode)
    {
        if (!$periode || $periode === 'all') {
            return $query;
        }

        $today = now()->toDateString();

        switch ($periode) {
            case 'masa_aktif':
                $query->where('masa_berlaku', '<=', $today)
                      ->where('sampai_tanggal', '>=', $today);
                break;
            case 'masa_tidak_aktif':
                $query->where(function ($q) use ($today) {
                    $q->where('masa_berlaku', '>', $today)
                      ->orWhere('sampai_tanggal', '<', $today);
                });
                break;
            case 'masa_habis_segera':
                $query->where('sampai_tanggal', '>=', $today)
                      ->where('sampai_tanggal', '<=', now()->addDays(30)->toDateString());
                break;
        }

        return $query;
    }
}