<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginLog extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'email',
        'ip_address',
        'user_agent',
        'status',
        'message'
    ];

    // Relasi ke User (opsional)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}