<?php

namespace App\Models\Memos;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MemoNumberSetting extends Model
{
    protected $table = 'memo_number_settings';

    protected $fillable = [
        'admin_id', 'prefix_kode', 'format_template', 'digit_padding',
        'reset_period', 'last_number', 'last_reset_year', 'last_reset_month', 'created_by',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Tentukan admin_id acuan penomoran untuk memo yang dibuat oleh $creator.
     *
     * - Kalau si pembuat sendiri terdaftar sebagai admin tim -> pakai id dia sendiri.
     * - Kalau dia anggota biasa -> pakai responsible_admin_id yang di-assign superadmin.
     * - Kalau tidak terdaftar di tim manapun -> null (pakai setting default/global).
     */
    public static function resolveAdminId(User $creator): ?int
    {
        $isAdmin = MemoTeamAdmin::where('user_id', $creator->id)->exists();
        if ($isAdmin) {
            return $creator->id;
        }

        $member = MemoTeamMember::where('user_id', $creator->id)->first();
        if ($member && $member->responsible_admin_id) {
            return $member->responsible_admin_id;
        }

        return null;
    }

    /**
     * Ambil team_id yang relevan untuk memo yang dibuat $creator (untuk kolom memos.team_id).
     * Kalau admin membawahi >1 tim, dipakai tim pertama yang dia administer (bisa dikembangkan
     * jadi pilihan dropdown di form create memo kalau dibutuhkan nanti).
     */
    public static function resolveTeamId(User $creator): ?int
    {
        $asAdmin = MemoTeamAdmin::where('user_id', $creator->id)->value('team_id');
        if ($asAdmin) {
            return $asAdmin;
        }

        return MemoTeamMember::where('user_id', $creator->id)->value('team_id');
    }

    /**
     * Ambil nama & jabatan penandatangan memo, diambil dari data admin acuan penomoran
     * ($creator sendiri kalau dia admin, atau admin penanggung jawabnya kalau dia anggota).
     * Nama & jabatan ini otomatis dipakai untuk mengisi field "Penandatangan" & "Jabatan" di memo.
     */
    public static function resolveSigner(User $creator): array
    {
        $adminId = self::resolveAdminId($creator);

        if (!$adminId) {
            return ['admin_id' => null, 'penandatangan' => $creator->name, 'jabatan' => null];
        }

        $admin = User::find($adminId);
        $jabatan = MemoTeamAdmin::where('user_id', $adminId)->whereNotNull('jabatan')->value('jabatan');

        return [
            'admin_id'      => $adminId,
            'penandatangan' => $admin->name ?? $creator->name,
            'jabatan'       => $jabatan,
        ];
    }

    /**
     * Generate nomor memo berikutnya untuk admin_id tertentu (null = setting default/global).
     * Row-lock dipakai supaya aman kalau ada 2 memo dibuat bersamaan oleh admin yang sama.
     */
    public static function generateNumberForAdmin(?int $adminId): string
    {
        return DB::transaction(function () use ($adminId) {
            $now = now();
            $year = $now->year;
            $month = $now->month;

            $setting = static::where('admin_id', $adminId)->lockForUpdate()->first();

            if (!$setting) {
                $adminName = $adminId ? optional(User::find($adminId))->name : null;
                $prefixKode = $adminName ? strtoupper(substr($adminName, 0, 3)) : 'UNK';

                $setting = static::create([
                    'admin_id'        => $adminId,
                    'prefix_kode'     => $prefixKode,
                    'format_template' => '{seq}/{prefix}/Fin/{bulan_romawi}/{tahun}',
                    'digit_padding'   => 3,
                    'reset_period'    => 'yearly',
                    'last_number'     => 0,
                    'last_reset_year' => $year,
                    'created_by'      => auth()->id() ?? $adminId,
                ]);
            }

            $needsReset = match ($setting->reset_period) {
                'yearly'  => $setting->last_reset_year !== $year,
                'monthly' => $setting->last_reset_year !== $year || $setting->last_reset_month !== $month,
                default   => false,
            };

            if ($needsReset) {
                $setting->last_number = 0;
                $setting->last_reset_year = $year;
                $setting->last_reset_month = $month;
            }

            $setting->last_number += 1;
            $setting->save();

            $romanMonths = [
                1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
                7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
            ];

            $seq = str_pad($setting->last_number, $setting->digit_padding, '0', STR_PAD_LEFT);

            return strtr($setting->format_template, [
                '{seq}'          => $seq,
                '{prefix}'       => $setting->prefix_kode,
                '{bulan_romawi}' => $romanMonths[$month],
                '{bulan}'        => str_pad($month, 2, '0', STR_PAD_LEFT),
                '{tahun}'        => $year,
            ]);
        });
    }
}
