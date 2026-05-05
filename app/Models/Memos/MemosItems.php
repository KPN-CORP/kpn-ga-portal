<?php

namespace App\Models\Memos;

use Illuminate\Database\Eloquent\Model;

class MemosItems extends Model
{
    protected $table = 'memos_items';

    protected $fillable = ['memo_id', 'nama', 'pt_unit', 'dynamic_columns', 'tagihan', 'sort_order'];

    protected $casts = [
        'dynamic_columns' => 'array',
        'tagihan' => 'decimal:2'
    ];

    public function memo()
    {
        return $this->belongsTo(Memos::class, 'memo_id');
    }
}