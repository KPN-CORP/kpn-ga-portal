<?php

namespace App\Models\Memos;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MemoTemplate extends Model
{
    protected $table = 'memo_templates';

    public const MAX_PER_TEAM = 15;

    protected $fillable = [
        'team_id', 'name', 'perihal', 'kepada', 'dari', 'instruksi',
        'bank', 'atas_nama', 'no_rek', 'sertakan_rekening', 'paragraf_pembuka', 'keterangan_label',
        'dynamic_columns_definition', 'items', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'dynamic_columns_definition' => 'array',
        'items' => 'array',
        'sertakan_rekening' => 'boolean',
    ];

    public function team()
    {
        return $this->belongsTo(MemoTeam::class, 'team_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId);
    }
}
