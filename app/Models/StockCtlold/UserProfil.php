<?php
namespace App\Models\StockCtl;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BisnisUnit; // Gunakan model yang sudah ada

class UserProfil extends Model
{
    protected $table = 'stock_ctl_user_profil';
    protected $primaryKey = 'id_user';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_user',
        'id_bisnis_unit',
        'id_area_kerja',
        'unit',
        'id_approver',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function bisnisUnit()
    {
        return $this->belongsTo(BisnisUnit::class, 'id_bisnis_unit', 'id_bisnis_unit');
    }

    public function areaKerja()
    {
        return $this->belongsTo(AreaKerja::class, 'id_area_kerja');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'id_approver');
    }

    public function bawahan()
    {
        return $this->hasMany(UserProfil::class, 'id_approver', 'id_user');
    }
}