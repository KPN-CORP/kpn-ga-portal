<?php
// app/Models/StockCtl/Transaksi.php
namespace App\Models\StockCtl;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Transaksi extends Model
{
    protected $table = 'stock_ctl_transaksi';
    protected $primaryKey = 'id_transaksi';
    public $timestamps = false;

    const STATUS_AKTIF = 'aktif';
    const STATUS_DIBATALKAN = 'dibatalkan';
    const STATUS_KOREKSI = 'koreksi';

    protected $fillable = [
        'jenis', 'id_barang', 'jumlah', 'id_area_asal', 'id_area_tujuan',
        'keterangan', 'id_user', 'no_ref',
        'status', 'id_transaksi_ref', 'dibatalkan_oleh', 'dibatalkan_pada', 'alasan_pembatalan',
    ];

    protected $casts = [
        'tanggal'         => 'datetime',
        'dibatalkan_pada' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_AKTIF,
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function areaAsal()
    {
        return $this->belongsTo(AreaKerja::class, 'id_area_asal');
    }

    public function areaTujuan()
    {
        return $this->belongsTo(AreaKerja::class, 'id_area_tujuan');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Transaksi asal yang dikoreksi/dibatalkan oleh baris ini (jika baris ini adalah koreksi).
     */
    public function transaksiRef()
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi_ref');
    }

    /**
     * Transaksi koreksi/pembalik yang dibuat dari baris ini (jika baris ini sudah dibatalkan).
     */
    public function transaksiKoreksi()
    {
        return $this->hasOne(Transaksi::class, 'id_transaksi_ref');
    }

    public function pembatal()
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    public function isDibatalkan()
    {
        return $this->status === self::STATUS_DIBATALKAN;
    }

    public function isKoreksi()
    {
        return $this->status === self::STATUS_KOREKSI;
    }
}