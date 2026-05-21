<?php
namespace App\Models\StockCtl;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BisnisUnit;

class AntarUnitRequest extends Model
{
    protected $table = 'stock_ctl_antar_unit_requests';
    public $timestamps = true;

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'disetujui';
    const STATUS_REJECTED = 'ditolak';

    protected $fillable = [
        'id_user_pemohon', 'id_barang', 'jumlah', 'id_bisnis_unit_asal',
        'id_bisnis_unit_tujuan', 'keterangan', 'status',
        'approved_by', 'approved_at', 'rejected_by', 'rejected_at', 'alasan_tolak'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function pemohon()
    {
        return $this->belongsTo(User::class, 'id_user_pemohon');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    public function unitAsal()
    {
        return $this->belongsTo(BisnisUnit::class, 'id_bisnis_unit_asal', 'id_bisnis_unit');
    }

    public function unitTujuan()
    {
        return $this->belongsTo(BisnisUnit::class, 'id_bisnis_unit_tujuan', 'id_bisnis_unit');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}