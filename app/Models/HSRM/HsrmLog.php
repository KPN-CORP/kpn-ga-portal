<?php

namespace App\Models\HSRM;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class HsrmLog extends Model
{
    protected $table = 'hsrm_logs';
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'action',
        'module',
        'record_id',
        'old_data',
        'new_data',
        'created_at',
    ];
    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
        'created_at' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}