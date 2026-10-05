<?php

namespace App\Models\Supplies;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SuppliesTransaksi extends Model
{
    protected $table = 'supplies_transaksi';
    public $timestamps = false;
    /**
     * Bisnis Unit sudah tidak dipakai di aplikasi, tapi kolom id_bisnis_unit di DB
     * masih NOT NULL tanpa default. Nilai ini otomatis terisi saat INSERT.
     */
    protected $attributes = ['id_bisnis_unit' => 6];

    protected $fillable = [
        'jenis', 'id_barang', 'jumlah', 'id_permintaan',
        'no_ref', 'keterangan', 'id_user', 'tanggal'
    ];
    protected $casts = ['tanggal' => 'datetime'];

    public function barang()
    {
        return $this->belongsTo(SuppliesBarang::class, 'id_barang');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function permintaan()
    {
        return $this->belongsTo(SuppliesPermintaan::class, 'id_permintaan');
    }

    /** Kapan permintaan diajukan (null untuk transaksi masuk / tanpa permintaan). */
    public function getTglDiajukanAttribute(): ?string
    {
        $tgl = $this->permintaan->created_at ?? null;

        return $tgl ? $tgl->format('d/m/Y H:i') : null;
    }

    /** Kapan permintaan di-approve (null untuk transaksi masuk / tanpa permintaan). */
    public function getTglApproveAttribute(): ?string
    {
        $tgl = $this->permintaan->approved_at ?? null;

        return $tgl ? $tgl->format('d/m/Y H:i') : null;
    }
}