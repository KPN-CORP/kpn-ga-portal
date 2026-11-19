<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SsoAuditLog extends Model
{
    protected $table = 'sso_audit_logs';

    protected $fillable = [
        'email',
        'sso_uid',
        'ip_address',
        'user_agent',
        'status',
        'message',
    ];
}
