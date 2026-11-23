<?php

namespace App\Models\Founddesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FounddeskItem extends Model
{
    use HasFactory;

    protected $table = 'founddesk_items';
    
    protected $fillable = [
        'item_code',
        'name',
        'description',
        'category_id',
        'location_id',
        'condition_id',
        'found_date',
        'found_by',
        'found_location_detail',
        'status',
        'current_stock',
        'unit',
        'photo',
        'is_active',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'found_date' => 'date',
        'is_active' => 'boolean',
        'current_stock' => 'integer'
    ];

    public function category()
    {
        return $this->belongsTo(FounddeskCategory::class, 'category_id');
    }

    public function location()
    {
        return $this->belongsTo(FounddeskLocation::class, 'location_id');
    }

    public function condition()
    {
        return $this->belongsTo(FounddeskCondition::class, 'condition_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function dispositions()
    {
        return $this->hasMany(FounddeskDisposition::class, 'item_id');
    }

    // Generate unique item code with format LF0001, LF0002, etc.
    public static function generateItemCode()
    {
        $prefix = 'LF';
        
        // Ambil item terakhir berdasarkan ID (urutan tertinggi)
        $lastItem = self::orderBy('id', 'desc')->first();
        
        if ($lastItem) {
            // Ambil nomor dari item_code terakhir (misal LF0001 -> 0001)
            $lastCode = $lastItem->item_code;
            $lastNumber = intval(substr($lastCode, 3)); // Ambil angka setelah LF
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // Jika belum ada data, mulai dari 0001
            $newNumber = '0001';
        }
        
        return $prefix . $newNumber;
    }

    // Get photo URL
    public function getPhotoUrlAttribute()
    {
        if ($this->photo) {
            return route('founddesk.photo', $this->id);
        }
        return null;
    }
}