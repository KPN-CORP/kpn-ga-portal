<?php

namespace App\Models\IDCard;

use Illuminate\Database\Eloquent\Model;

/**
 * Daftar kartu fisik pinjam (magang / visitor). Tidak terkait stok.
 * Status "Digunakan" / "Ada" dihitung dari request_idcard oleh IDCardStokService::statusKartuPinjam().
 */
class IdCardKartuPinjam extends Model
{
    protected $table = 'idcard_kartu_pinjam';

    public const JENIS_LABELS = [
        'magang'  => 'Magang',
        'visitor' => 'Visitor',
        'umum'    => 'Magang & Visitor',
    ];

    protected $fillable = ['nomor_kartu', 'jenis', 'keterangan', 'is_active', 'created_by'];

    protected $casts = ['is_active' => 'boolean'];

    public function getJenisTextAttribute(): string
    {
        return self::JENIS_LABELS[$this->jenis] ?? (string) $this->jenis;
    }
}
