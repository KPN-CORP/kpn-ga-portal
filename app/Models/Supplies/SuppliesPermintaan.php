<?php

namespace App\Models\Supplies;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SuppliesPermintaan extends Model
{
    protected $table = 'supplies_permintaan';
    /**
     * Bisnis Unit sudah tidak dipakai di aplikasi, tapi kolom id_bisnis_unit di DB
     * masih NOT NULL tanpa default. Nilai ini otomatis terisi saat INSERT.
     */
    protected $attributes = ['id_bisnis_unit' => 6];

    protected $fillable = [
        'id_user_pemohon', 'id_barang', 'jumlah',
        'keterangan', 'status', 'approved_by', 'approved_at', 'alasan_tolak'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function pemohon()
    {
        return $this->belongsTo(User::class, 'id_user_pemohon');
    }

    public function barang()
    {
        return $this->belongsTo(SuppliesBarang::class, 'id_barang');
    }


    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Lama dari diajukan sampai diproses (approve/tolak).
     * Kalau masih pending, dihitung sampai sekarang.
     */
    public function getLamaProsesAttribute(): string
    {
        $akhir = $this->approved_at ?? now();
        $detik = max(0, $akhir->timestamp - $this->created_at->timestamp);

        if ($detik < 60) {
            return $detik . ' dtk';
        }

        $menit = intdiv($detik, 60);
        if ($menit < 60) {
            return $menit . ' mnt';
        }

        $jam = intdiv($menit, 60);
        if ($jam < 24) {
            return $jam . ' jam' . ($menit % 60 ? ' ' . ($menit % 60) . ' mnt' : '');
        }

        return intdiv($jam, 24) . ' hari' . ($jam % 24 ? ' ' . ($jam % 24) . ' jam' : '');
    }
}
