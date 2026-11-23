<?php

namespace App\Models\StockCtl;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin stock-ctl yang boleh approve/tolak L1 atas nama atasan (diisi lewat database).
 * Cakupan permintaan tetap mengikuti unit bisnis admin tersebut (superadmin: semua unit).
 */
class L1OverrideAdmin extends Model
{
    protected $table = 'stock_ctl_l1_override_admins';

    protected $fillable = ['user_id', 'note'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}