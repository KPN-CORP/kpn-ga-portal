<?php
namespace App\Models\Supplies;

use Illuminate\Database\Eloquent\Model;
use App\Models\BisnisUnit;

class SuppliesStok extends Model
{
    protected $table = 'supplies_stok';
    public $timestamps = false;
    protected $fillable = ['id_barang', 'id_bisnis_unit', 'jumlah'];

    public function barang()
    {
        return $this->belongsTo(SuppliesBarang::class, 'id_barang');
    }

    public function bisnisUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'id_bisnis_unit', 'id_bisnis_unit');
    }
}