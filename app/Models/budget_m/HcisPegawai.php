<?php

namespace App\Models\budget_m;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabel referensi HCIS (hanya dibaca, tidak pernah diubah).
 * group_company = BU, company_name = PT.
 */
class HcisPegawai extends Model
{
    protected $table = 'api_emp_hcis';
    public $timestamps = false;

    public static function daftarBu(): array
    {
        return static::query()
            ->whereNotNull('group_company')->where('group_company', '<>', '')
            ->distinct()->orderBy('group_company')->pluck('group_company')->all();
    }

    public static function daftarPt(?string $bu = null): array
    {
        return static::query()
            ->whereNotNull('company_name')->where('company_name', '<>', '')
            ->when($bu, fn ($q) => $q->where('group_company', $bu))
            ->distinct()->orderBy('company_name')->pluck('company_name')->all();
    }

    public static function pasanganAda(?string $bu, ?string $pt): bool
    {
        return static::query()->where('group_company', $bu)->where('company_name', $pt)->exists();
    }
}
