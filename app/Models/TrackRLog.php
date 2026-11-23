<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackRLog extends Model
{
    protected $table = 'track_r_logs';

    protected $fillable = [
        'track_r_document_id',
        'aksi',
        'dari_user_id',
        'ke_user_id',
        'catatan',
    ];

    public function dariUser()
    {
        return $this->belongsTo(User::class, 'dari_user_id');
    }

    public function keUser()
    {
        return $this->belongsTo(User::class, 'ke_user_id');
    }
}
