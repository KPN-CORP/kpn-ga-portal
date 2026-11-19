<?php

namespace App\Models\Founddesk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class FounddeskDisposition extends Model
{
    use HasFactory;

    protected $table = 'founddesk_dispositions';
    
    protected $fillable = [
        'disposition_no',
        'item_id',
        'quantity',
        'disposition_date',
        'recipient_name',
        'recipient_id',
        'recipient_contact',
        'id_card_photo',
        'handover_photo',
        'notes',
        'status',
        'created_by',
        'approved_by',
        'approved_at'
    ];

    protected $casts = [
        'disposition_date' => 'date',
        'approved_at' => 'datetime',
        'quantity' => 'integer'
    ];

    public function item()
    {
        return $this->belongsTo(FounddeskItem::class, 'item_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Generate disposition number with format SR0001, SR0002, etc.
    public static function generateDispositionNo()
    {
        $prefix = 'SR';
        
        // Ambil disposition terakhir berdasarkan ID (urutan tertinggi)
        $lastDisposition = self::orderBy('id', 'desc')->first();
        
        if ($lastDisposition) {
            // Ambil nomor dari disposition_no terakhir (misal SR0001 -> 0001)
            $lastCode = $lastDisposition->disposition_no;
            $lastNumber = intval(substr($lastCode, 2)); // Ambil angka setelah SR
            $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // Jika belum ada data, mulai dari 0001
            $newNumber = '0001';
        }
        
        return $prefix . $newNumber;
    }
}