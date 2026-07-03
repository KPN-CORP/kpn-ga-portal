<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Messkar\MesBooking;
use App\Models\Messkar\MesNotifikasi;
use App\Models\Messkar\MesRiwayat;
use App\Models\StockCtl\UserProfil;
use App\Models\Memos\Memos;
use Illuminate\Support\Facades\DB;
use App\Models\HSRM\HsrmUserRole;

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
    
    // =====================================================
    // RELATIONSHIP UNTUK MESS KARYAWAN
    // =====================================================
    
    /**
     * Get the mess bookings for the user.
     */
    public function messBookings()
    {
        return $this->hasMany(MesBooking::class, 'id_user', 'id');
    }
    
    /**
     * Get the mess notifikasi for the user.
     */
    public function messNotifikasi()
    {
        return $this->hasMany(MesNotifikasi::class, 'id_user', 'id');
    }
    
    /**
     * Get the mess riwayat for the user.
     */
    public function messRiwayat()
    {
        return $this->hasMany(MesRiwayat::class, 'id_user', 'id');
    }
    
    /**
     * Get bookings that need approval (for admin)
     */
    public function pendingApprovals()
    {
        return $this->hasMany(MesBooking::class, 'approved_by', 'id');
    }
    
    // =====================================================
    // EXISTING METHODS
    // =====================================================
    
    // Cek apakah user memiliki akses full ke GA Help
    public function hasFullGaHelpAccess()
    {
        if ($this->accessMenu && $this->accessMenu->ga_help_full_akses == 1) {
            return true;
        }
        
        return false;
    }
    
    // Cek apakah user adalah admin mess (bisa ditambahkan sendiri)
    public function isMessAdmin()
    {
        // Misal: user dengan akses GA Help full atau role tertentu
        return $this->hasFullGaHelpAccess() || $this->username == 'admin';
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

    public function profil()
    {
        return $this->hasOne(\App\Models\StockCtl\UserProfil::class, 'id_user');
    }

    // =====================================================
    // RELATIONSHIP & METHODS UNTUK DRMS (DITAMBAHKAN)
    // =====================================================

    /**
     * Relasi ke data karyawan dari HCIS (api_emp_hcis)
     */
    public function employee()
    {
        return $this->belongsTo(\App\Models\ApiEmpHcis::class, 'employee_no', 'employee_id');
    }

    /**
     * Mendapatkan bawahan langsung (jika user adalah manager L1)
     */
    public function subordinates()
    {
        return $this->hasMany(\App\Models\ApiEmpHcis::class, 'manager_l1_id', 'employee_no');
    }
    /**
     * Relasi ke tabel drms_user (opsional, jika menggunakan tabel khusus)
     */
    public function drmsUser()
    {
        return $this->hasOne(\App\Models\Drms\User::class, 'user_id', 'id');
    }
    public function drmsProfile()
    {
        return $this->hasOne(\App\Models\Drms\DrmsUserProfile::class, 'user_id');
    }

    public function isDrmsUser(): bool
    {
        return $this->drmsProfile && $this->drmsProfile->is_drms_user;
    }

    public function isApprover(): bool
    {
        return $this->drmsProfile && $this->drmsProfile->is_approver;
    }

    public function isDrmsAdmin()
    {
        return $this->drmsProfile && $this->drmsProfile->is_drms_admin;
    }

    // public function isDrmsSuperAdmin(): bool
    // {
    //     // Cek dari accessMenu (jika ada kolom drms_superadmin) atau dari stock_ctl_superadmin
    //     return $this->accessMenu && ($this->accessMenu->drms_superadmin || $this->accessMenu->stock_ctl_superadmin);
    // }

    public function isDrmsSuperAdmin(): bool
    {
        return $this->accessMenu && $this->accessMenu->drms_superadmin == 1;
    }

    public function hasDrmsAllBuAccess(): bool
    {
        // Superadmin otomatis true
        if ($this->isDrmsSuperAdmin()) {
            return true;
        }

        // Cek dari accessMenu
        return $this->accessMenu && $this->accessMenu->drms_all_bu_access == 1;
    }

    /**
     * Mendapatkan business unit ID user (dari profile)
     */
    public function getBusinessUnitIdAttribute()
    {
        return $this->drmsProfile ? $this->drmsProfile->business_unit_id : null;
    }

    // =====================================================
    // METHODS UNTUK WORK REPORT (DITAMBAHKAN)
    // =====================================================

    public function isWorkUser()
    {
        return $this->accessMenu && $this->accessMenu->work_user == 1;
    }

    public function isWorkAdmin()
    {
        return $this->accessMenu && $this->accessMenu->work_admin == 1;
    }

    public function driver()
    {
        return $this->hasOne(\App\Models\Drms\Driver::class, 'username', 'username');
    }

    public function isDriver()
    {
        return $this->driver()->exists();
    }

    /**
     * Mendapatkan area user (dari profile)
     */
    public function getAreaAttribute()
    {
        return $this->drmsProfile ? $this->drmsProfile->area : null;
    }

    // =====================================================
    // METHODS UNTUK E-MEMO (DITAMBAHKAN)
    // =====================================================

    public function memos()
    {
        return $this->hasMany(\App\Models\Memos\Memos::class, 'created_by');
    }

    /**
     * Cek apakah user memiliki akses sebagai user E-Memo
     */
    public function isMemoUser()
    {
        return $this->accessMenu && $this->accessMenu->memo_user == 1;
    }

    /**
     * Cek apakah user memiliki akses sebagai admin E-Memo
     */
    public function isMemoAdmin()
    {
        return $this->accessMenu && $this->accessMenu->memo_admin == 1;
    }

    /**
     * Cek apakah user memiliki akses sebagai superadmin E-Memo
     */
    public function isMemoSuperadmin()
    {
        return $this->accessMenu && $this->accessMenu->memo_superadmin == 1;
    }

    /**
     * Mendapatkan business unit dari employee (group_company)
     * Digunakan untuk filtering di E-Memo
     */
    public function getBusinessUnitAttribute()
    {
        // Prioritas: dari kolom business_unit_code (jika ada) atau dari relasi employee
        if (!empty($this->business_unit_code)) {
            return $this->business_unit_code;
        }
        
        if ($this->employee) {
            return $this->employee->group_company;
        }
        
        return null;
    }

    // Backward compatibility untuk method isSuperadmin/isAdmin (jika masih diperlukan)
    // Bisa juga menggunakan isMemoSuperadmin dan isMemoAdmin sebagai pengganti
    public function isSuperadmin()
    {
        return $this->isMemoSuperadmin();
    }

    public function isAdmin()
    {
        return $this->isMemoAdmin();
    }

    public function isFeedbackAdmin()
    {
        return \App\Models\Feedbacks\FeedbackAdmin::where('user_id', $this->id)->exists();
    }

    // =====================================================
    // RELATIONSHIP & METHODS UNTUK TASK MONITOR
    // =====================================================

    public function taskMonitors()
    {
        return $this->hasMany(\App\Models\Task_M\TaskMonitor::class, 'user_id');
    }

    public function isTaskMonitorSuperadmin()
    {
        return $this->accessMenu && $this->accessMenu->is_superadmin == 1;
    }

    public function accessDash()
    {
        return $this->hasOne(\App\Models\AccessDash::class, 'username_access', 'username');
    }

    // app/Models/User.php
    public function hsrmAreas()
    {
        return $this->belongsToMany(\App\Models\AreaKerja::class, 'hsrm_user_roles', 'user_id', 'area_id')
                    ->wherePivot('role', 'pic');
    }

    public function hsrmRoles()
    {
        return $this->hasMany(\App\Models\HSRM\HsrmUserRole::class);
    }

    public function isHsrmAdmin()
    {
        return $this->hsrmRoles()->where('role', 'admin')->exists();
    }

    public function isHsrmPic()
    {
        return $this->hsrmRoles()->where('role', 'pic')->exists();
    }

    public function getHsrmRoleAttribute()
    {
        if ($this->isHsrmAdmin()) return 'admin';
        if ($this->isHsrmPic()) return 'pic';
        return null;
    }

    public function hsrmRoleInArea($areaId)
    {
        $role = $this->belongsToMany(AreaKerja::class, 'hsrm_user_roles', 'user_id', 'area_id')
            ->wherePivot('area_id', $areaId)
            ->first();
        return $role ? $role->pivot->role : null;
    }


    public function hsrmUserRoles()
    {
        return $this->hasMany(HsrmUserRole::class);
    }

    public function canEditInArea($areaId)
    {
        if (session('hsrm_role') === 'admin') {
            return true;
        }
        return $this->hsrmUserRoles()
            ->where('area_id', $areaId)
            ->whereIn('role', ['admin', 'pic'])
            ->exists();
    }

    public function canApproveInArea($areaId)
    {
        if (session('hsrm_role') === 'admin') {
            return true;
        }
        return $this->hsrmUserRoles()
            ->where('area_id', $areaId)
            ->where('can_approve', true)
            ->exists();
    }

    public function isSuperadminTrack()
    {
        return DB::table('tb_access_menu')
            ->where('username', $this->username) // sesuaikan kolom identifier jika berbeda
            ->value('superadmin_track') == 1;
    }
}