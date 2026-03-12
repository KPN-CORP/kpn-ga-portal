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

    protected $fillable = [
        'jenis', 'id_barang', 'jumlah', 'id_area_asal', 'id_area_tujuan',
        'keterangan', 'id_user', 'no_ref'
    ];

    protected $casts = [
        'tanggal' => 'datetime',
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
}