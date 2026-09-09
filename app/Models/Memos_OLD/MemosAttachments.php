<?php

namespace App\Models\Memos;

use Illuminate\Database\Eloquent\Model;

class MemosAttachments extends Model
{
    protected $table = 'memos_attachments';

    protected $fillable = ['memo_id', 'file_path', 'original_name', 'mime_type', 'is_checked'];

    public function memo()
    {
        return $this->belongsTo(Memos::class, 'memo_id');
    }
}