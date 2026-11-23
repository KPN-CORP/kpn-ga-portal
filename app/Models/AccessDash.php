<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessDash extends Model
{
    use HasFactory;

    protected $table = 'tb_access_dash';
    protected $primaryKey = 'id_access';
    public $timestamps = true;

    protected $fillable = [
        'username_access',
        'bu_access',
        'idcard_dash',
        'messenger_dash',
        'ma_room_dash',
        'receipt_dash',
        'employees_dash',
        'reports_dash',
        'hsr_dash',
        'hsr_admin_dash',
    ];

    protected $casts = [
        'idcard_dash' => 'boolean',
        'messenger_dash' => 'boolean',
        'ma_room_dash' => 'boolean',
        'receipt_dash' => 'boolean',
        'employees_dash' => 'boolean',
        'reports_dash' => 'boolean'
    ];

    /**
     * Relasi ke User (jika ada tabel users)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'username_access', 'username');
    }

    /**
     * Cek apakah user memiliki akses ke semua module
     */
    public function hasFullAccess()
    {
        return $this->idcard_dash && $this->messenger_dash && $this->ma_room_dash 
            && $this->receipt_dash && $this->employees_dash && $this->reports_dash;
    }

    /**
     * Hitung total module yang diakses
     */
    public function totalAccessCount()
    {
        $count = 0;
        $modules = ['idcard_dash', 'messenger_dash', 'ma_room_dash', 
                   'receipt_dash', 'employees_dash', 'reports_dash'];
        
        foreach ($modules as $module) {
            if ($this->$module) $count++;
        }
        
        return $count;
    }

    /**
     * Dapatkan list module yang bisa diakses
     */
    public function getAccessibleModules()
    {
        $modules = [];
        
        if ($this->messenger_dash) $modules[] = 'messenger';
        if ($this->ma_room_dash) $modules[] = 'mailing';
        if ($this->receipt_dash) $modules[] = 'trackreceipt';
        if ($this->idcard_dash) $modules[] = 'idcard';
        if ($this->employees_dash) $modules[] = 'employees';
        if ($this->reports_dash) $modules[] = 'reports';
        
        return $modules;
    }
}