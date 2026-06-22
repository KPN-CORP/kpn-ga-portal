<?php

namespace App\Models\Memos;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Memos extends Model
{
    protected $table = 'memos';

    protected $fillable = [
        'memo_number', 'perihal', 'kepada', 'dari', 'instruksi', 'bank',
        'atas_nama', 'no_rek', 'penandatangan', 'jabatan', 'total_amount',
        'status', 'business_unit', 'dynamic_columns_definition', 'created_by', 'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'dynamic_columns_definition' => 'array',
    ];

    protected static function booted()
    {
        static::creating(function ($memo) {
            $memo->memo_number = static::generateMemoNumber($memo);
        });
    }

    /**
     * Generate nomor memo unik per bisnis unit per tahun
     * Format: {no_urut}/HC-{kode_bisnis}/Fin/{bulan_romawi}/{tahun}
     */
    public static function generateMemoNumber($memo)
    {
        $businessUnit = $memo->business_unit;
        $year = now()->year;
        $month = now()->month;

        // Mapping nama bisnis unit ke kode (3 huruf)
        $buCodeMap = [
            'KPN Corporation' => 'CRP',
            'KPN Corporatio'  => 'CRP',
            'KPN Plantations' => 'PLT',
            'Cement'          => 'CMT',
            'Property'        => 'PRT',
            'Properti'        => 'PRT',
            'Downstream'      => 'DWS',
            'MSL'             => 'MSL',
        ];

        // Ambil kode, jika tidak ada gunakan 3 huruf pertama
        $code = $buCodeMap[$businessUnit] ?? strtoupper(substr($businessUnit, 0, 3));
        if (empty($code) || strlen($code) < 3) {
            $code = 'UNK';
        }

        // Hitung jumlah memo pada bisnis unit yang sama di tahun yang sama
        $lastNumber = static::where('business_unit', $businessUnit)
                            ->whereYear('created_at', $year)
                            ->count();
        $sequential = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        // Konversi bulan angka ke Romawi
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        $romanMonth = $romanMonths[$month];

        return "{$sequential}/HC-{$code}/Fin/{$romanMonth}/{$year}";
    }

    // ========== RELATIONSHIPS ==========
    public function items()
    {
        return $this->hasMany(MemosItems::class, 'memo_id');
    }

    public function attachments()
    {
        return $this->hasMany(MemosAttachments::class, 'memo_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ========== SCOPE ==========
    public function scopeViewable($query, User $user)
    {
        if ($user->isMemoSuperadmin()) {
            return $query;
        }
        if ($user->isMemoAdmin()) {
            return $query->where('business_unit', $user->getBusinessUnitAttribute());
        }
        if ($user->isMemoUser()) {
            return $query->where('created_by', $user->id);
        }
        return $query->whereRaw('1 = 0');
    }
}