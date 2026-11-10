<?php

namespace App\Models\budget_m;

use Illuminate\Database\Eloquent\Model;

/** Pengaturan sederhana (kunci => nilai). Dipakai untuk tarif PPN dan nilai materai. */
class BudgetPengaturan extends Model
{
    protected $table = 'budget_pengaturan';
    protected $primaryKey = 'kunci';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['kunci', 'nilai'];

    public static function ambil(string $kunci, $bawaan = null)
    {
        $v = static::query()->whereKey($kunci)->value('nilai');
        return $v === null ? $bawaan : $v;
    }

    public static function simpan(string $kunci, $nilai): void
    {
        static::query()->updateOrCreate(['kunci' => $kunci], ['nilai' => (string) $nilai]);
    }

    public static function ppnPersen(): float
    {
        return (float) static::ambil('ppn_persen', 11);
    }

    public static function materaiNominal(): float
    {
        return (float) static::ambil('materai_nominal', 10000);
    }
}
