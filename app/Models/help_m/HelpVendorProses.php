<?php

namespace App\Models\help_m;

use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpVendorProses extends Model
{
    protected $table = 'db_help_vendor_proses';

    protected $fillable = [
        'tiket_id',
        'nama_vendor',
        'catatan',
        'diserahkan_oleh',
        'mulai_pada',
        'selesai_pada',
    ];

    protected $casts = [
        'mulai_pada'   => 'datetime',
        'selesai_pada' => 'datetime',
    ];

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(HelpTiket::class, 'tiket_id');
    }

    public function penyerah(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'diserahkan_oleh', 'id_pelanggan');
    }

    /**
     * Lama pengerjaan vendor dalam detik (sampai selesai, atau sampai sekarang bila masih berjalan).
     */
    public function getDurasiDetikAttribute(): int
    {
        $akhir = $this->selesai_pada ?? now();

        return max(0, $akhir->timestamp - $this->mulai_pada->timestamp);
    }

    /**
     * Lama pengerjaan vendor dalam hari penuh (1 hari = 24 jam, tanpa pembulatan ke atas).
     */
    public function getDurasiHariAttribute(): int
    {
        return intdiv($this->durasi_detik, 86400);
    }
}