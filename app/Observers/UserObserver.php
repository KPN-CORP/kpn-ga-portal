<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * Jalan SETELAH user berhasil dibuat (SSO)
     */
    public function created(User $user): void
    {
        DB::transaction(function () use ($user) {

            $username = $user->username ?? $user->employee_no;

            /**
             * =========================
             * 1. tb_pelanggan
             * =========================
             */
            $existsPelanggan = DB::table('tb_pelanggan')
                ->where('username_pelanggan', $username)
                ->exists();

            if (!$existsPelanggan) {
                DB::table('tb_pelanggan')->insert([
                    'id_login'          => $user->id,
                    'nama_pelanggan'    => $user->name ?? trim($user->first_name.' '.$user->last_name),
                    'username_pelanggan'=> $username,
                    'password'          => bcrypt(Str::random(12)),
                    'bisnis_unit'       => $user->company_name,
                    'departemen'        => $user->office_city,
                    'no_hp_pelanggan'   => $user->office_mobile ?: '000000000000',
                    'email_pelanggan'   => $user->email,
                    'gambar'            => 'default.jpg',
                    'role_akses'        => 'Pelanggan',
                ]);
            }

            /**
             * =========================
             * 2. tb_access_menu
             * (TANPA created_at)
             * =========================
             */
            $existsMenu = DB::table('tb_access_menu')
                ->where('username', $username)
                ->exists();

            if (!$existsMenu) {
                DB::table('tb_access_menu')->insert([
                    'username'              => $username,
                    'request_idcard'        => 0,
                    'list_idcard'           => 0,
                    'detail_idcard'         => 0,
                    'proses_idcard'         => 0,
                    'request_messenger'     => 1,
                    'status_messenger'      => 1,
                    'detail_messenger'      => 1,
                    'akses_messenger_all'   => 0,
                    'akses_messenger'       => 1,
                    'proses_messenger'      => 0,
                    'emp_index'             => 0,
                    'emp_show'              => 0,
                    'emp_edit'              => 0,
                    'mailing_list'          => 1,
                    'mailing_input'         => 0,
                    'mailing_edit'          => 0,
                    'mailing_proses'        => 0,
                    'ga_help_proses'        => 0,
                ]);
            }

            /**
             * =========================
             * 3. tb_access_dash
             * (TANPA created_at)
             * =========================
             */
            $existsDash = DB::table('tb_access_dash')
                ->where('username_access', $username)
                ->exists();

            if (!$existsDash) {
                DB::table('tb_access_dash')->insert([
                    'username_access'       => $username,
                    'bu_access'             => null,
                    'messenger_dash'        => 1,
                    'ma_room_dash'          => 1,
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
                ]);
            }
        });
    }
}
