<?php

namespace App\Models\budget_m;

use App\Models\User;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Database\Eloquent\Model;

class BudgetKategori extends Model
{
    protected $table = 'budget_kategori';
    protected $fillable = ['nama', 'dept_head_user_id', 'urut', 'aktif'];
    protected $casts = ['aktif' => 'boolean'];

    public function subs()
    {
        return $this->hasMany(BudgetSub::class, 'kategori_id')->orderBy('urut')->orderBy('nama');
    }

    /**
     * Kunci urutan tampil: nama Dept (A-Z) dulu, lalu nama orangnya, baru angka "urut", lalu nama kategori.
     * Kategori tanpa pemilik ("Umum") paling akhir.
     */
    public function kunciUrut(): array
    {
        return [
            $this->dept_head_user_id ? 0 : 1,
            mb_strtolower(BudgetPeran::namaDept($this->dept_head_user_id)),
            mb_strtolower(BudgetPeran::namaOrang($this->dept_head_user_id)),
            (int) $this->urut,
            mb_strtolower($this->nama),
        ];
    }

    public function pemilik()
    {
        return $this->belongsTo(User::class, 'dept_head_user_id');
    }

    /** Admin: semua. Dept Head: miliknya. PIC: milik Dept Head-nya. Kategori tanpa pemilik (lama) terlihat semua. */
    public function scopeTerlihatOleh($q, $user = null)
    {
        $user = $user ?? auth()->user();
        $peran = BudgetPeran::peran($user);

        if ($peran === BudgetPeran::ADMIN) {
            return $q;
        }
        $ids = match ($peran) {
            BudgetPeran::DEPT_HEAD => [(int) $user->id],
            BudgetPeran::PIC => BudgetPicHead::where('pic_user_id', $user->id)->where('aktif', true)
                ->pluck('dept_head_user_id')->map(fn ($x) => (int) $x)->all(),
            default => [],
        };

        return $q->where(fn ($w) => $w->whereNull('dept_head_user_id')->orWhereIn('dept_head_user_id', $ids));
    }
}