<?php

namespace App\Models\Memos;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MemoRevision extends Model
{
    protected $table = 'memo_revisions';

    protected $fillable = [
        'memo_id', 'revision_number', 'snapshot', 'changed_fields', 'revised_by'
    ];

    protected $casts = [
        'snapshot'       => 'array',
        'changed_fields' => 'array',
    ];

    public function memo()
    {
        return $this->belongsTo(Memos::class, 'memo_id');
    }

    public function revisedBy()
    {
        return $this->belongsTo(User::class, 'revised_by');
    }
}
