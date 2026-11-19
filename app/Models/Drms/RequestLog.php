<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class RequestLog extends Model
{
    protected $table = 'drms_request_logs';

    protected $fillable = [
        'request_id', 'action', 'from_status', 'to_status', 'note', 'detail', 'user_id',
    ];

    public function request()
    {
        return $this->belongsTo(DriverRequest::class, 'request_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
