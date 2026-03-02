<?php

namespace App\Models\Founddesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FounddeskCategory extends Model
{
    use HasFactory;

    protected $table = 'founddesk_categories';
    
    protected $fillable = [
        'name',
        'description'
    ];

    public function items()
    {
        return $this->hasMany(FounddeskItem::class, 'category_id');
    }
}