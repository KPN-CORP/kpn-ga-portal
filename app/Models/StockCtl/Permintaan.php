<?php
namespace App\Models\StockCtl;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Permintaan extends Model
{
    protected $table = 'stock_ctl_permintaan';
    protected $primaryKey = 'id_permintaan';
    public $timestamps = false;

    // Status constants
    const STATUS_PENDING_L1 = 'pending_l1';
    const STATUS_PENDING_ADMIN = 'pending_admin';
    const STATUS_APPROVED = 'disetujui';
    const STATUS_REJECTED = 'ditolak';

    protected $fillable = [
        'id_user_pemohon', 'id_barang', 'jumlah', 'keterangan', 'status',
        'id_approver', 'tanggal_approval', 'alasan_tolak', 'id_area_kerja',
        'approved_l1_by', 'approved_l1_at', 'approved_admin_by', 'approved_admin_at',
        'rejected_by', 'rejected_at', 'rejection_reason'
    ];

    protected $casts = [
        'tanggal_permintaan' => 'datetime',
        'tanggal_approval' => 'datetime',
        'approved_l1_at' => 'datetime',
        'approved_admin_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Relasi ke pemohon (user yang mengajukan)
     */
    public function pemohon()
    {
        return $this->belongsTo(User::class, 'id_user_pemohon');
    }

    /**
     * Relasi ke barang
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    /**
     * Relasi ke atasan yang menyetujui level 1 (L1)
     */
    public function approverL1()
    {
        return $this->belongsTo(User::class, 'approved_l1_by');
    }

    /**
     * Relasi ke admin yang menyetujui level 2 (final)
     */
    public function approverAdmin()
    {
        return $this->belongsTo(User::class, 'approved_admin_by');
    }

    /**
     * Relasi ke user yang menolak (bisa L1 atau admin)
     */
    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Relasi ke area kerja
     */
    public function areaKerja()
    {
        return $this->belongsTo(AreaKerja::class, 'id_area_kerja');
    }

    /**
     * Relasi 'approver' untuk kompatibilitas dengan kode lama.
     * Mengembalikan admin approval (final) jika ada.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_admin_by');
    }
}