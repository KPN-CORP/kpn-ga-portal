<?php
namespace App\Models\StockCtl;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $table = 'stock_ctl_barang';
    protected $primaryKey = 'id_barang';
    public $timestamps = false;

    protected $fillable = [
        'kode_barang', 
        'nama_barang', 
        'satuan', 
        'harga', 
        'deskripsi'
    ];

    public function stok()
    {
        return $this->hasMany(Stok::class, 'id_barang');
    }

    public function transaksi()
    {
        return $this->hasMany(Transaksi::class, 'id_barang');
    }
}