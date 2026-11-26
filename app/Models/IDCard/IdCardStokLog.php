<?php

namespace App\Models\IDCard;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class IdCardStokLog extends Model
{
    protected $table = 'idcard_stok_log';

    public const TIPE_LABELS = [
        'masuk'        => 'Stok Masuk',
        'keluar'       => 'Stok Keluar',
        'penyesuaian'  => 'Opname / Penyesuaian',
    ];

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'item_id', 'tipe', 'qty', 'saldo_setelah', 'request_id', 'catatan', 'created_by', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime', 'qty' => 'integer', 'saldo_setelah' => 'integer'];

    public function item()
    {
        return $this->belongsTo(IdCardItem::class, 'item_id');
    }

    /** Admin pemilik stok (yang stoknya bertambah / berkurang). */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function request()
    {
        return $this->belongsTo(RequestIdCard::class, 'request_id');
    }

    public function getTipeTextAttribute(): string
    {
        return self::TIPE_LABELS[$this->tipe] ?? (string) $this->tipe;
    }
}
