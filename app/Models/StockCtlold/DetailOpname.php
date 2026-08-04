<?php
namespace App\Models\StockCtl;

use Illuminate\Database\Eloquent\Model;

class DetailOpname extends Model
{
    protected $table = 'stock_ctl_detail_opname';
    protected $primaryKey = 'id_detail_opname';
    public $timestamps = false;

    protected $fillable = [
        'id_opname', 
        'id_barang', 
        'stok_sistem', 
        'stok_fisik', 
        'keterangan'
    ];

    public function opname()
    {
        return $this->belongsTo(Opname::class, 'id_opname');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}