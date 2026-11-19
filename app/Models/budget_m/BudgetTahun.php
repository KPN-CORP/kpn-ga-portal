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
            static::ingat($diminta);
            return $diminta;
        }
        $daftar = static::daftar();
        // Tahun yang terakhir dipilih tetap dipakai saat pindah menu, supaya tidak kembali ke tahun berjalan tanpa peringatan
        $ingat = (int) static::ingat();
        if ($ingat > 0 && in_array($ingat, $daftar, true)) {
            return $ingat;
        }
        return in_array((int) now()->year, $daftar, true) ? (int) now()->year : (int) max($daftar);
    }

    /** Baca / simpan tahun terakhir yang dipilih pengguna di sesi. */
    protected static function ingat(?int $tahun = null): ?int
    {
        try {
            if (!app()->bound('session') || !request()->hasSession()) {
                return null;
            }
            if ($tahun !== null) {
                session(['budget_tahun_pilih' => $tahun]);
                return $tahun;
            }
            return session('budget_tahun_pilih');
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected static ?array $memoDaftar = null;

    /** Daftar tahun anggaran (terbaru dulu). Dimuat sekali per request, karena dipakai berkali-kali di satu halaman. */
    public static function daftar(): array
    {
        if (static::$memoDaftar === null) {
            $d = static::query()->orderByDesc('tahun')->pluck('tahun')->map(fn ($v) => (int) $v)->all();
            static::$memoDaftar = $d ?: [(int) now()->year];
        }
        return static::$memoDaftar;
    }

    public static function lupakanDaftar(): void
    {
        static::$memoDaftar = null;
    }

    public static function terkunci(int $tahun): bool
    {
        return (bool) static::query()->where('tahun', $tahun)->value('terkunci');
    }
}
