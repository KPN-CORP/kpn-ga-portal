<?php
namespace App\Models\StockCtl;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class LaporanHistory extends Model
{
    protected $table = 'stock_ctl_laporan_history';
    protected $primaryKey = 'id_history';
    public $timestamps = false;

    protected $fillable = [
        'id_user',
        'jenis',
        'id_area',
        'id_barang',
        'tanggal_awal',
        'tanggal_akhir',
        'nama_file',
    ];

    protected $casts = [
        'dicetak_pada' => 'datetime',
        'tanggal_awal' => 'date',
        'tanggal_akhir' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function area()
    {
        return $this->belongsTo(AreaKerja::class, 'id_area');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }
}