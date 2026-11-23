<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;

class BisnisUnit extends Model
{
    protected $table = 'tb_bisnis_unit';
    protected $primaryKey = 'id_bisnis_unit';
    public $timestamps = false;

    protected $fillable = ['nama_bisnis_unit'];

    public function units()
    {
        return $this->hasMany(ApartemenUnit::class, 'bisnis_unit_id', 'id_bisnis_unit');
    }

    /**
     * Resolusi nama Bisnis Unit milik $user (siapa pun, bukan cuma yang sedang login).
     * Dipakai untuk mencatat Bisnis Unit pemohon (bukan pemilik unit) ke history.
     *
     * Logika sama seperti UserController::getUserBisnisUnitId():
     * 1) Kolom bisnis_unit_id langsung di tabel users (kalau ada).
     * 2) Fallback: cocokkan group_company (tb_access_menu, berdasarkan username) ke nama_bisnis_unit.
     */
    public static function resolveNamaForUser($user): ?string
    {
        if (!$user) {
            return null;
        }

        // 1) Kolom bisnis_unit_id langsung di user
        if (!empty($user->bisnis_unit_id)) {
            $bu = static::find($user->bisnis_unit_id);
            if ($bu) {
                return $bu->nama_bisnis_unit;
            }
        }

        // 2) Fallback: group_company di tb_access_menu, dicocokkan ke nama_bisnis_unit
        $username = $user->username ?? $user->name ?? null;
        if ($username) {
            $groupCompany = \Illuminate\Support\Facades\DB::table('tb_access_menu')
                ->where('username', $username)
                ->value('group_company');

            if ($groupCompany) {
                $bu = static::where('nama_bisnis_unit', $groupCompany)->first();
                // Kalau ketemu di master, pakai nama resminya; kalau tidak, pakai apa adanya.
                return $bu ? $bu->nama_bisnis_unit : $groupCompany;
            }
        }

        return null;
    }
}