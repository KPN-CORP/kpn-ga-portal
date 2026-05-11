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

    public function handle()
    {
        $this->info('Mulai sinkronisasi dengan override approver...');

        $users = User::all();
        $bar = $this->output->createProgressBar(count($users));
        $bar->start();

        foreach ($users as $user) {
            // Cari data HCIS
            $hcis = ApiEmpHcis::where('employee_id', $user->employee_no)->first();

            // Cari business unit ID berdasarkan group_company
            $businessUnitId = null;
            if ($hcis && $hcis->group_company) {
                $unit = BisnisUnit::where('nama_bisnis_unit', $hcis->group_company)->first();
                $businessUnitId = $unit?->id_bisnis_unit;
            }

            // === OVERRIDE APPROVER ===
            // Cek apakah ada override untuk user ini di tabel stock_ctl_approver_override
            $override = DB::table('stock_ctl_approver_override')
                ->where('id_user', $user->id)
                ->first();

            $approverUserId = null;
            if ($override && $override->id_approver) {
                // Gunakan override jika ada
                $approverUserId = $override->id_approver;
                $this->line("  [OVERRIDE] User {$user->username} menggunakan approver override ID: {$approverUserId}");
            } else {
                // Tidak ada override, cari approver dari manager_l1_id seperti biasa
                if ($hcis && $hcis->manager_l1_id) {
                    $approver = User::where('employee_no', $hcis->manager_l1_id)->first();
                    $approverUserId = $approver?->id;
                }
            }

            // Apakah user ini memiliki bawahan? (is_approver)
            $isApprover = $hcis && ApiEmpHcis::where('manager_l1_id', $user->employee_no)->exists();

            // Ambil flag akses dari tb_access_menu (relasi accessMenu)
            $access = $user->accessMenu;
            $isDrmsUser = $access && $access->drms_user;
            $isDrmsAdmin = $access && $access->drms_admin;

            // Update atau insert ke drms_user_profiles
            DrmsUserProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'business_unit_id' => $businessUnitId,
                    'unit' => $hcis->unit ?? null,
                    'area' => $hcis->office_area ?? null,
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