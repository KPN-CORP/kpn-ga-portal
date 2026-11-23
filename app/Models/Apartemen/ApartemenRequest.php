<?php

namespace App\Models\Apartemen;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class ApartemenRequest extends Model
{
    use SoftDeletes;

    protected $table = 'tb_apartemen_request';
    protected $fillable = [
        'user_id',
        'tanggal_pengajuan',
        'status',
        'alasan',
        'approved_by',
        'approved_at',
        'reject_reason'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'tanggal_pengajuan' => 'date'
    ];

    protected $dates = ['deleted_at'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penghuni()
    {
        return $this->hasMany(ApartemenRequestPenghuni::class, 'request_id');
    }

    public function assign()
    {
        return $this->hasOne(ApartemenAssign::class, 'request_id');
    }

    public function getStatusTextAttribute()
    {
        $statuses = [
            'PENDING' => 'Tertunda',
            'APPROVED' => 'Disetujui',
            'REJECTED' => 'Ditolak'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'PENDING' => 'yellow',
            'APPROVED' => 'green',
            'REJECTED' => 'red'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    public function getTotalKapasitasDibutuhkanAttribute()
    {
        return $this->penghuni()->count();
    }
}