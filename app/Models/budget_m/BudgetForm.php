<?php

namespace App\Models\budget_m;

use App\Models\User;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Database\Eloquent\Model;

/** Form realisasi buatan pengguna. Satu form mengisi satu item budget. */
class BudgetForm extends Model
{
    protected $table = 'budget_form';
    protected $fillable = ['nama', 'deskripsi', 'dept_head_user_id', 'item_id', 'pakai_ppn', 'pakai_materai', 'aktif', 'dibuat_oleh'];
    protected $casts = ['pakai_ppn' => 'boolean', 'pakai_materai' => 'boolean', 'aktif' => 'boolean'];

    protected static ?array $memoKunci = null;

    public function item()
    {
        return $this->belongsTo(BudgetItem::class, 'item_id');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** Semua field (termasuk yang diarsipkan), berurutan. */
    public function fields()
    {
        return $this->hasMany(BudgetFormField::class, 'form_id')->orderBy('urut')->orderBy('id');
    }

    public function fieldAktif()
    {
        return $this->fields()->where('aktif', true);
    }

    public function isian()
    {
        return $this->hasMany(BudgetFormIsian::class, 'form_id');
    }

    /** Admin: semua form. Dept dan PIC: form milik Dept-nya (PIC melihat yang sama dengan Dept-nya). */
    public function scopeTerlihatOleh($q, $user)
    {
        $peran = BudgetPeran::peran($user);
        if ($peran === BudgetPeran::ADMIN) {
            return $q;
        }
        if ($peran === BudgetPeran::DEPT_HEAD || $peran === BudgetPeran::PIC) {
            return $q->whereIn('dept_head_user_id', BudgetPeran::kepalaIds($user));
        }
        return $q->whereRaw('1 = 0');
    }

    /**
     * Item yang terkunci dari input manual = item yang dipakai form AKTIF.
     * Dimuat sekali per request. Panggil lupakanKunci() setelah form diubah dalam request yang sama.
     */
    public static function idItemTerkunci(): array
    {
        if (static::$memoKunci === null) {
            static::$memoKunci = static::query()->where('aktif', true)->pluck('item_id')->map(fn ($v) => (int) $v)->unique()->values()->all();
        }
        return static::$memoKunci;
    }

    public static function itemTerkunci(int $itemId): bool
    {
        return in_array($itemId, static::idItemTerkunci(), true);
    }

    /** Nama form aktif yang mengunci item ini (untuk pesan ke pengguna). */
    public static function namaPengunci(int $itemId): ?string
    {
        return static::query()->where('aktif', true)->where('item_id', $itemId)->orderBy('id')->value('nama');
    }

    public static function lupakanKunci(): void
    {
        static::$memoKunci = null;
    }
}
