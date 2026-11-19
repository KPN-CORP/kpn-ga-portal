<?php
namespace App\Models\Supplies;

use Illuminate\Database\Eloquent\Model;

class SuppliesStok extends Model
{
    protected $table = 'supplies_stok';
    public $timestamps = false;
    /**
     * Bisnis Unit sudah tidak dipakai di aplikasi, tapi kolom id_bisnis_unit di DB
     * masih NOT NULL tanpa default. Nilai ini otomatis terisi saat INSERT.
     */
    protected $attributes = ['id_bisnis_unit' => 6];

    protected $fillable = ['id_barang', 'jumlah'];

    public function barang()
    {
        return $this->belongsTo(SuppliesBarang::class, 'id_barang');
    }

}