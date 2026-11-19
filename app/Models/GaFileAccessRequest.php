<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaFileAccessRequest extends Model
{
    protected $table = 'ga_file_access_requests';

    protected $guarded = [];

    protected $casts = [
        'claimed_at'   => 'datetime',
        'lease_until'  => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function file()
    {
        return $this->belongsTo(GaFile::class, 'file_id');
    }
}
