<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\ApiEmpHcis;
use App\Models\BisnisUnit;
use App\Models\Drms\DrmsUserProfile;
use Illuminate\Support\Facades\DB;

class SyncDrmsUserProfiles extends Command
{
    protected $signature = 'drms:sync-profiles';
    protected $description = 'Sinkronisasi data user untuk DRMS ke tabel drms_user_profiles (dengan override approver)';

    // Profil ID yang field-field tertentunya dikunci (tidak ikut dihitung ulang dari HCIS/override).
    // business_unit_id & unit TETAP ikut sync normal walau ID-nya ada di sini.
    protected $lockedProfileIds = [44, 133, 1121];

    public function handle()
    {
        $this->info('Mulai sinkronisasi dengan override approver...');

        $users = User::all();
        $bar = $this->output->createProgressBar(count($users));
        $bar->start();

        // Daftar user_id yang dipakai sebagai id_approver di tabel override.
        // Siapapun di daftar ini WAJIB is_approver = true, walau tidak punya bawahan di HCIS.
        $overriddenApproverIds = DB::table('stock_ctl_approver_override')
            ->pluck('id_approver')
            ->unique()
            ->toArray();

        foreach ($users as $user) {
            // Cari data HCIS
            $hcis = ApiEmpHcis::where('employee_id', $user->employee_no)->first();

            // Cari business unit ID berdasarkan group_company (SELALU ikut sync, tidak dikunci)
            $businessUnitId = null;
            if ($hcis && $hcis->group_company) {
                $unit = BisnisUnit::where('nama_bisnis_unit', $hcis->group_company)->first();
                $businessUnitId = $unit?->id_bisnis_unit;
            }

            // Cari profil yang sudah ada untuk user ini
            $existingProfile = DrmsUserProfile::where('user_id', $user->id)->first();
            $isLocked = $existingProfile && in_array($existingProfile->id, $this->lockedProfileIds);

            // === OVERRIDE APPROVER ===
            $override = DB::table('stock_ctl_approver_override')
                ->where('id_user', $user->id)
                ->first();

            $approverUserId = null;
            if ($override && $override->id_approver) {
                $approverUserId = $override->id_approver;
                $this->line("  [OVERRIDE] User {$user->username} menggunakan approver override ID: {$approverUserId}");
            } else {
                if ($hcis && $hcis->manager_l1_id) {
                    $approver = User::where('employee_no', $hcis->manager_l1_id)->first();
                    $approverUserId = $approver?->id;
                }
            }

            // Apakah user ini memiliki bawahan? (is_approver, dari HCIS)
            $isApprover = $hcis && ApiEmpHcis::where('manager_l1_id', $user->employee_no)->exists();

            // Ambil flag akses dari tb_access_menu (relasi accessMenu)
            $access = $user->accessMenu;
            $isDrmsUser = $access && $access->drms_user;
            $isDrmsAdmin = $access && $access->drms_admin;

            // Nilai default area dari HCIS
            $areaToSave = $hcis->office_area ?? null;

            // ==========================================================
            // LOCK: untuk profil ID 44, 133, 1121 -> area, approver_user_id,
            // is_approver, is_drms_user, is_drms_admin TIDAK ikut dihitung ulang.
            // business_unit_id & unit tetap ikut sync HCIS seperti biasa.
            // ==========================================================
            if ($isLocked) {
                $areaToSave     = $existingProfile->area;
                $approverUserId = $existingProfile->approver_user_id;
                $isApprover     = $existingProfile->is_approver;
                $isDrmsUser     = $existingProfile->is_drms_user;
                $isDrmsAdmin    = $existingProfile->is_drms_admin;

                $this->line("  [LOCKED] User {$user->username} (ID profil {$existingProfile->id}): area, approver_user_id, is_approver, is_drms_user, is_drms_admin dipertahankan dari data lama.");
            }
            // ==========================================================

            // Paksa is_approver = true kalau user ini dipakai sebagai id_approver override untuk siapapun.
            // PENTING: blok ini sengaja dijalankan SETELAH blok LOCKED, supaya aturan ini yang jadi
            // keputusan final untuk is_approver — locked ataupun tidak. Field lain (area,
            // approver_user_id, is_drms_user, is_drms_admin) tetap ikut aturan lock seperti biasa.
            if (in_array($user->id, $overriddenApproverIds)) {
                if (!$isApprover) {
                    $this->line("  [FORCE-APPROVER] User {$user->username} dijadikan is_approver=true karena dipakai sebagai override L1.");
                }
                $isApprover = true;
            }

            // Update atau insert ke drms_user_profiles
            DrmsUserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_unit_id' => $businessUnitId,
                    'unit' => $hcis->unit ?? null,
                    'area' => $areaToSave,
                    'approver_user_id' => $approverUserId,
                    'is_approver' => $isApprover,
                    'is_drms_user' => $isDrmsUser,
                    'is_drms_admin' => $isDrmsAdmin,
                ]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Sinkronisasi selesai!');
    }
}