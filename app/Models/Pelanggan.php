<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    protected $table = 'tb_pelanggan';
    protected $primaryKey = 'id_pelanggan';
    
    protected $fillable = [
        'id_login',
        'employee_no',
        'lantai_aktif',
        'nama_pelanggan',
        'username_pelanggan',
        'password',
        'bisnis_unit',
        'departemen',
        'pic',
        'no_hp_pelanggan',
        'email_pelanggan',
        'gambar',
        'role_akses',
        'first_name',
        'last_name',
    ];
    
    protected $hidden = [
        'password',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // Relationship ke users
    public function user()
    {
        return $this->belongsTo(User::class, 'id_login', 'id');
    }
    
    // Relationship ke mailing sebagai PENERIMA (sudah ada)
    public function mailings()
    {
        return $this->hasMany(Mailing::class, 'mailing_penerima_id', 'id_pelanggan');
    }
    
    // Accessor untuk nama lengkap
    public function getNamaLengkapAttribute()
    {
        if ($this->first_name && $this->last_name) {
            return trim($this->first_name . ' ' . $this->last_name);
        } elseif ($this->first_name) {
            return $this->first_name;
        } elseif ($this->last_name) {
            return $this->last_name;
        } else {
            return $this->username_pelanggan ?? 'Unknown';
        }
    }
    
    // Accessor untuk inisial
    public function getInisialAttribute()
    {
        if ($this->first_name && $this->last_name) {
            return strtoupper(substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1));
        } elseif ($this->first_name) {
            return strtoupper(substr($this->first_name, 0, 1));
        } elseif ($this->last_name) {
            return strtoupper(substr($this->last_name, 0, 1));
        } else {
            return strtoupper(substr($this->username_pelanggan ?? 'U', 0, 1));
        }
    }
    
    // Relationships Help Tiket
    public function tiket()
    {
        return $this->hasMany(\App\Models\help_m\HelpTiket::class, 'pelapor_id', 'id_pelanggan');
    }
    
    public function tiketDitugaskan()
    {
        return $this->hasMany(\App\Models\help_m\HelpTiket::class, 'ditugaskan_ke', 'id_pelanggan');
    }
}