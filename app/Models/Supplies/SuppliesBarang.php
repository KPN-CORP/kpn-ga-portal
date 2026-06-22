<?php
namespace App\Models\Supplies;

use Illuminate\Database\Eloquent\Model;

class SuppliesBarang extends Model
{
    protected $table = 'supplies_barang';
    protected $fillable = ['kode_barang', 'nama_barang', 'satuan', 'harga', 'deskripsi', 'lokasi_rak', 'stok_minimum'];

    public function stok()
    {
        return $this->hasMany(SuppliesStok::class, 'id_barang');
    }
}