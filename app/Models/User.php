<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use Notifiable;
    
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'sso_uid',
        'employee_no',
        'first_name',
        'last_name',
        'company_name',
        'office_city',
        'office_mobile',
        'login_type'
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    // Relationship ke tb_pelanggan via id_login
    public function pelanggan()
    {
        return $this->hasOne(Pelanggan::class, 'id_login', 'id');
    }
    
    // Relationship ke tb_access_menu
    public function accessMenu()
    {
        return $this->hasOne(AccessMenu::class, 'username', 'username');
    }
    
    // Cek apakah user memiliki akses full ke GA Help
    public function hasFullGaHelpAccess()
    {
        if ($this->accessMenu && $this->accessMenu->ga_help_full_akses == 1) {
            return true;
        }
        
        return false;
    }
    
    // Mendapatkan bisnis unit ID yang bisa diakses user
    public function getAccessibleBusinessUnits()
    {
        // Jika full access, kembalikan semua unit
        if ($this->hasFullGaHelpAccess()) {
            return BisnisUnit::pluck('id_bisnis_unit')->toArray();
        }
        
        // Jika tidak, hanya unit miliknya sendiri
        if ($this->pelanggan && $this->pelanggan->bisnis_unit) {
            // Cari ID bisnis unit berdasarkan nama
            $unit = BisnisUnit::where('nama_bisnis_unit', $this->pelanggan->bisnis_unit)->first();
            if ($unit) {
                return [$unit->id_bisnis_unit];
            }
        }
        
        return [];
    }
    
    // Cek apakah user bisa mengakses tiket tertentu
    public function canAccessTicket($tiket)
    {
        // Full access bisa akses semua
        if ($this->hasFullGaHelpAccess()) {
            return true;
        }
        
        // Cek berdasarkan bisnis unit
        $accessibleUnits = $this->getAccessibleBusinessUnits();
        
        // Jika tiket memiliki bisnis_unit_id dan ada dalam daftar akses
        if ($tiket->bisnis_unit_id && in_array($tiket->bisnis_unit_id, $accessibleUnits)) {
            return true;
        }
        
        // Jika user adalah penanggung jawab tiket
        if ($this->pelanggan && $tiket->ditugaskan_ke == $this->pelanggan->id_pelanggan) {
            return true;
        }
        
        return false;
    }
    
    // Method untuk mencari pelanggan berdasarkan employee_no atau username
    public function findMatchingPelanggan()
    {
        // Cari berdasarkan employee_no
        if ($this->employee_no) {
            $pelanggan = Pelanggan::where('employee_no', $this->employee_no)->first();
            if ($pelanggan) {
                return $pelanggan;
            }
        }
        
        // Cari berdasarkan username_pelanggan (bandingkan dengan username atau email)
        if ($this->username) {
            $pelanggan = Pelanggan::where('username_pelanggan', $this->username)->first();
            if ($pelanggan) {
                return $pelanggan;
            }
        }
        
        // Cari berdasarkan email
        $pelanggan = Pelanggan::where('email_pelanggan', $this->email)->first();
        if ($pelanggan) {
            return $pelanggan;
        }
        
        return null;
    }
    
    // Method untuk link user dengan pelanggan
    public function linkWithPelanggan(Pelanggan $pelanggan)
    {
        $pelanggan->id_login = $this->id;
        $pelanggan->save();
        return $pelanggan;
    }
}