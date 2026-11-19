<?php

namespace App\Models\budget_m;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Hanya ditulis lewat BudgetCatat::tulis(). Tidak pernah diubah atau dihapus oleh aplikasi. */
class BudgetLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'budget_log';
    protected $fillable = ['user_id', 'aksi', 'objek', 'objek_id', 'item_id', 'tahun', 'keterangan', 'sebelum', 'sesudah', 'ip'];
    protected $casts = ['sebelum' => 'array', 'sesudah' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
