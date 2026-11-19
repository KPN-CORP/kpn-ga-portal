<?php

namespace App\Models\help_m;

use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpLogStatus extends Model
{
    protected $table = 'db_help_log_status';
    protected $guarded = [];
    public $timestamps = true;

    /** Awalan catatan untuk log pengalihan penanggung jawab (dibaca HelpTimeline & tampilan Timeline Status). */
    const PREFIX_ALIH = 'Pengalihan penanggung jawab';

    /** Awalan catatan saat pelapor membuka kembali tiket DONE (dibaca HelpTimeline). */
    const PREFIX_BUKA_KEMBALI = 'Dibuka kembali oleh pelapor';

    /** Awalan catatan saat pelapor mengonfirmasi tiket selesai. */
    const PREFIX_KONFIRMASI = 'Ditutup oleh pelapor';

    public function getIsPengalihanAttribute(): bool
    {
        return str_starts_with((string) $this->catatan, self::PREFIX_ALIH);
    }

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(HelpTiket::class, 'tiket_id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pengguna_id', 'id_pelanggan');
    }
}