<?php
// app/Models/StockCtl/Stok.php
namespace App\Models\StockCtl;

use Illuminate\Database\Eloquent\Model;

class Stok extends Model
{
    protected $table = 'stock_ctl_stok';
    protected $primaryKey = 'id_stok';
    public $timestamps = false;

    protected $fillable = ['id_barang', 'id_area_kerja', 'jumlah', 'stok_minimum'];

    protected $casts = [
        'last_update' => 'datetime',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function areaKerja()
    {
        return $this->belongsTo(AreaKerja::class, 'id_area_kerja');
    }
}