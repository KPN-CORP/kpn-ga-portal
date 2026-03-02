<?php

namespace App\Models\Founddesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FounddeskLocation extends Model
{
    use HasFactory;

    protected $table = 'founddesk_locations';
    
    protected $fillable = [
        'name',
        'description'
    ];

    public function items()
    {
        return $this->hasMany(FounddeskItem::class, 'location_id');
    }

    public function transfersFrom()
    {
        return $this->hasMany(FounddeskTransfer::class, 'from_location_id');
    }

    public function transfersTo()
    {
        return $this->hasMany(FounddeskTransfer::class, 'to_location_id');
    }
}