<?php
// app/Models/StockCtl/Opname.php
namespace App\Models\StockCtl;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Opname extends Model
{
    protected $table = 'stock_ctl_opname';
    protected $primaryKey = 'id_opname';
    public $timestamps = false;

    protected $fillable = ['id_area_kerja', 'tanggal_opname', 'id_user', 'status'];

    protected $casts = [
        'tanggal_opname' => 'date',
    ];

    public function areaKerja()
    {
        return $this->belongsTo(AreaKerja::class, 'id_area_kerja');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function details()
    {
        return $this->hasMany(DetailOpname::class, 'id_opname');
    }
}