<?php

namespace App\Models\Founddesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FounddeskCondition extends Model
{
    use HasFactory;

    protected $table = 'founddesk_conditions';
    
    protected $fillable = [
        'name',
        'description'
    ];

    public function items()
    {
        return $this->hasMany(FounddeskItem::class, 'condition_id');
    }
}