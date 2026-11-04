<?php

namespace App\Models\budget_m;

use Illuminate\Database\Eloquent\Model;

class BudgetTahun extends Model
{
    protected $table = 'budget_tahun';
    protected $fillable = ['tahun', 'terkunci', 'dikunci_oleh', 'dikunci_pada'];
    protected $casts = ['terkunci' => 'boolean', 'dikunci_pada' => 'datetime'];

    /** Tahun yang dipakai halaman: dari query, kalau kosong tahun berjalan (bila ada), kalau tidak tahun terbaru. */
    public static function pilih($diminta = null): int
    {
        $diminta = (int) $diminta;
        if ($diminta > 0) {
            return $diminta;
        }
        $daftar = static::daftar();
        return in_array((int) now()->year, $daftar, true) ? (int) now()->year : (int) max($daftar);
    }

    public static function daftar(): array
    {
        $d = static::query()->orderByDesc('tahun')->pluck('tahun')->map(fn ($v) => (int) $v)->all();
        return $d ?: [(int) now()->year];
    }

    public static function terkunci(int $tahun): bool
    {
        return (bool) static::query()->where('tahun', $tahun)->value('terkunci');
    }
}
