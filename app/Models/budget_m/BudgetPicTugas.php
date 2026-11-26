<?php

namespace App\Models\budget_m;

use App\Models\User;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Database\Eloquent\Model;

/**
 * Penugasan PIC pada satu kategori, subkategori, atau item milik seorang Dept Head.
 * Hanya berlaku bila Dept Head mengaktifkan saklar "Batasi PIC" (budget_akses.batasi_pic).
 */
class BudgetPicTugas extends Model
{
    protected $table = 'budget_pic_tugas';
    protected $fillable = ['dept_head_user_id', 'pic_user_id', 'kategori_id', 'sub_id', 'item_id', 'dibuat_oleh'];

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    /** Hapus semua penugasan seorang PIC (opsional: hanya di satu Dept Head). Aman bila tabel belum dibuat. */
    public static function hapusPic(int $picId, ?int $headId = null): void
    {
        if (!BudgetPeran::fiturPicAktif()) {
            return;
        }
        static::where('pic_user_id', $picId)->when($headId, fn ($q, $h) => $q->where('dept_head_user_id', $h))->delete();
    }

    /** Hapus semua penugasan di bawah seorang Dept Head. */
    public static function hapusHead(int $headId): void
    {
        if (!BudgetPeran::fiturPicAktif()) {
            return;
        }
        static::where('dept_head_user_id', $headId)->delete();
    }
}
