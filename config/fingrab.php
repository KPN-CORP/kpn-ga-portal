<?php

return [
    // Username yang selalu boleh akses dan melihat semua Bisnis unit (sama seperti ID Card).
    'superadmin_username' => 'admin',

    // Gerbang 1: ikon E-FinGrab di GA Portal. Pengguna harus punya nilai 1 di kolom ini
    // (kolom yang sama dengan yang dipakai dashboard untuk menampilkan ikon: $access->fingrab_dash).
    'menu_table'  => 'tb_access_dash',
    'menu_key'    => 'username_access',
    'menu_column' => 'fingrab_dash',

    // Gerbang 2: cakupan Bisnis unit (user_id, bisnis_unit_id), polanya sama dengan request_idcard_accesbu.
    'access_table' => 'fingrab_accesbu',

    // Nama di tb_bisnis_unit yang melihat semua Bisnis unit.
    'corporate_bu' => 'KPN Corporation',
];
