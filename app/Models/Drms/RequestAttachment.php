<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RequestAttachment extends Model
{
    protected $table = 'drms_request_attachments';

    protected $fillable = [
        'request_id', 'file_path', 'original_name', 'mime_type', 'file_size', 'uploaded_by',
    ];

    public function request()
    {
        return $this->belongsTo(DriverRequest::class, 'request_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
