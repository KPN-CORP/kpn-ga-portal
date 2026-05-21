<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncAccessFromPelanggan extends Command
{
    protected $signature = 'sync:access-pelanggan';
    protected $description = 'Sync tb_access_menu & tb_access_dash dari tb_pelanggan';

    public function handle()
    {
        $pelanggan = DB::table('tb_pelanggan')
            ->whereNotNull('username_pelanggan')
            ->get();

        if ($pelanggan->isEmpty()) {
            $this->warn('Tidak ada data pelanggan.');
            return Command::SUCCESS;
        }

        foreach ($pelanggan as $p) {

            /** =========================
             * tb_access_menu
             * ========================= */
            DB::table('tb_access_menu')->updateOrInsert(
                ['username' => $p->username_pelanggan],
                [
                    'request_idcard'        => 0,
                    'list_idcard'           => 0,
                    'detail_idcard'         => 0,
                    'proses_idcard'         => 0,
                    'request_messenger'     => 1,
                    'status_messenger'      => 1,
                    'detail_messenger'      => 1,
                    'akses_messenger_all'   => 0,
                    'akses_messenger'       => 0,
                    'proses_messenger'      => 0,
                    'emp_index'             => 0,
                    'emp_show'              => 0,
                    'emp_edit'              => 0,
                    'mailing_list'          => 1,
                    'mailing_input'         => 0,
                    'mailing_edit'          => 0,
                    'mailing_proses'        => 0,
                    'ga_help_proses'        => 0,
                ]
            );

            /** =========================
             * tb_access_dash
             * ========================= */
            DB::table('tb_access_dash')->updateOrInsert(
                ['username_access' => $p->username_pelanggan],
                [
                    'bu_access'             => null,
                    'messenger_dash'        => 1,
                    'ma_room_dash'          => 0,
                    'receipt_dash'          => 1,
                    'idcard_dash'           => 0,
                    'car_dash'              => 1,
                    'apart_dash'            => 0,
                    'receptionist_dash'     => 0,
                    'helpdest_dash'         => 1,
                    'employees_dash'        => 0,
                    'reports_dash'          => 0,
                    'messenger_admin_dash'  => 0,
                    'apart_admin_dash'      => 0,
                    'car_admin_dash'        => 0,
                    'helpdesk_admin_dash'   => 0,
                    'maroom_admin_dash'     => 0,
                ]
            );
        }

        $this->info('✅ Sync akses dari tb_pelanggan selesai');
        return Command::SUCCESS;
    }
}
