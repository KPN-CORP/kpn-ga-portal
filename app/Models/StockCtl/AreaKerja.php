<?php
namespace App\Models\StockCtl;

use Illuminate\Database\Eloquent\Model;
use App\Models\BisnisUnit; // Gunakan model yang sudah ada

class AreaKerja extends Model
{
    protected $table = 'stock_ctl_area_kerja';
    protected $primaryKey = 'id_area_kerja';
    public $timestamps = false;

    protected $fillable = ['nama_area', 'id_bisnis_unit'];

    public function bisnisUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'id_bisnis_unit', 'id_bisnis_unit');
    }

    public function stok()
    {
        return $this->hasMany(Stok::class, 'id_area_kerja');
    }
}