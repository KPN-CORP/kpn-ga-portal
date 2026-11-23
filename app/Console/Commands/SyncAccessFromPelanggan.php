<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncAccessFromPelanggan extends Command
{
    protected $signature = 'sync:access-pelanggan';
    protected $description = 'Sync tb_access_menu & tb_access_dash dari tb_pelanggan';

    /**
     * Hanya office_area ini yang mendapat profil akses.
     * Selain ini (termasuk NULL) semua flag diset 0.
     */
    private const ALLOWED_OFFICE_AREAS = [
        'Corporate Gama Tower',
        'HO Gama Tower',
        'HO Arandra Residences',
        'Unit Arandra Residences',
        'Unit Gama Tower',
    ];

    private const MENU_ACCESS = [
        'request_idcard'      => 0,
        'list_idcard'         => 0,
        'detail_idcard'       => 0,
        'proses_idcard'       => 0,
        'request_messenger'   => 1,
        'status_messenger'    => 1,
        'detail_messenger'    => 1,
        'akses_messenger_all' => 0,
        'akses_messenger'     => 0,
        'proses_messenger'    => 0,
        'emp_index'           => 0,
        'emp_show'            => 0,
        'emp_edit'            => 0,
        'mailing_list'        => 1,
        'mailing_input'       => 0,
        'mailing_edit'        => 0,
        'mailing_proses'      => 0,
        'ga_help_proses'      => 0,
        'stock_user'          => 0,
        'stock_ctl_user'      => 0,
    ];

    private const DASH_ACCESS = [
        'messenger_dash'       => 1,
        'ma_room_dash'         => 0,
        'receipt_dash'         => 1,
        'idcard_dash'          => 0,
        'car_dash'             => 1,
        'apart_dash'           => 0,
        'receptionist_dash'    => 0,
        'helpdest_dash'        => 1,
        'employees_dash'       => 0,
        'reports_dash'         => 0,
        'messenger_admin_dash' => 0,
        'apart_admin_dash'     => 0,
        'car_admin_dash'       => 0,
        'helpdesk_admin_dash'  => 0,
        'maroom_admin_dash'    => 0,
        'stock_dash'           => 0,
        // feedback_dash sengaja tidak ditulis: pakai default DB (1)
    ];

    private function isAllowedArea($value): bool
    {
        $value = trim((string) $value);

        foreach (self::ALLOWED_OFFICE_AREAS as $allowed) {
            if (strcasecmp($value, $allowed) === 0) {
                return true;
            }
        }

        return false;
    }

    private function zeroed(array $access): array
    {
        return array_map(fn () => 0, $access);
    }

    public function handle()
    {
        $pelanggan = DB::table('tb_pelanggan')
            ->whereNotNull('username_pelanggan')
            ->get();

        if ($pelanggan->isEmpty()) {
            $this->warn('Tidak ada data pelanggan.');
            return Command::SUCCESS;
        }

        $aktif = 0;
        $nol   = 0;

        foreach ($pelanggan as $p) {
            $username = $p->username_pelanggan;

            /** =========================
             * tb_access_menu
             * ========================= */
            $menuArea    = DB::table('tb_access_menu')
                ->where('username', $username)
                ->value('office_area');
            $menuAllowed = $this->isAllowedArea($menuArea);

            DB::table('tb_access_menu')->updateOrInsert(
                ['username' => $username],
                $menuAllowed
                    ? self::MENU_ACCESS
                    : $this->zeroed(self::MENU_ACCESS)
            );

            /** =========================
             * tb_access_dash
             * ========================= */
            $dashArea    = DB::table('tb_access_dash')
                ->where('username_access', $username)
                ->value('office_area');
            $dashAllowed = $this->isAllowedArea($dashArea);

            $dash = $dashAllowed
                ? self::DASH_ACCESS
                : $this->zeroed(self::DASH_ACCESS);

            $dash['bu_access'] = null;

            DB::table('tb_access_dash')->updateOrInsert(
                ['username_access' => $username],
                $dash
            );

            if ($menuAllowed || $dashAllowed) {
                $aktif++;
            } else {
                $nol++;
            }
        }

        $this->info("✅ Sync selesai. Akses diberikan: {$aktif}, di-0-kan (area tidak diizinkan): {$nol}");
        return Command::SUCCESS;
    }
}