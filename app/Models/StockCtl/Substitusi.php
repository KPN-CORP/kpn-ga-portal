<?php
namespace App\Models\StockCtl;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Substitusi extends Model
{
    protected $table = 'stock_ctl_substitusi';
    protected $primaryKey = 'id_substitusi';
    public $timestamps = false;

    protected $fillable = [
        'id_permintaan', 'no_permintaan',
        'id_barang_asli', 'nama_asli', 'jumlah_diminta',
        'id_barang_pengganti', 'nama_pengganti', 'jumlah_pengganti',
        'alasan', 'id_admin', 'id_transaksi', 'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function permintaan()
    {
        return $this->belongsTo(Permintaan::class, 'id_permintaan');
    }

    public function barangAsli()
    {
        return $this->belongsTo(Barang::class, 'id_barang_asli');
    }

    public function barangPengganti()
    {
        return $this->belongsTo(Barang::class, 'id_barang_pengganti');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'id_admin');
    }

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi');
    }
}
