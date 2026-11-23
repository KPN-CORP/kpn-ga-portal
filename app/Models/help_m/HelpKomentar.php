<?php

namespace App\Models\help_m;

use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpKomentar extends Model
{
    protected $table = 'db_help_komentar';
    protected $guarded = [];
    public $timestamps = true;

    public function tiket(): BelongsTo
    {
        return $this->belongsTo(HelpTiket::class, 'tiket_id');
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pengguna_id', 'id_pelanggan');
    }
}