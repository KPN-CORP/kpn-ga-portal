<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Versi migration Laravel dari file .sql di folder sql/ (pilih salah satu, jangan dua-duanya).
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `drms_fuel_logs`
            ADD COLUMN `photo_dashboard_before` varchar(255) NULL DEFAULT NULL AFTER `receipt_file`,
            ADD COLUMN `is_draft` tinyint(1) NOT NULL DEFAULT 0 AFTER `photo_dashboard_after`,
            MODIFY COLUMN `fuel_liters` decimal(10,2) NULL DEFAULT NULL,
            ADD INDEX `idx_fuel_is_draft`(`is_draft`)");

        DB::statement("ALTER TABLE `drms_expense_reports`
            MODIFY COLUMN `category` enum('toll','parkir','bbm','cuci_mobil','lainnya') NOT NULL");
    }

    public function down(): void
    {
        // Draft belum punya liter -> hapus dulu sebelum fuel_liters dikembalikan NOT NULL
        DB::statement("DELETE FROM `drms_fuel_logs` WHERE `is_draft` = 1");
        DB::statement("DELETE FROM `drms_expense_reports` WHERE `category` = 'lainnya'");

        DB::statement("ALTER TABLE `drms_fuel_logs`
            DROP INDEX `idx_fuel_is_draft`,
            DROP COLUMN `is_draft`,
            DROP COLUMN `photo_dashboard_before`,
            MODIFY COLUMN `fuel_liters` decimal(10,2) NOT NULL");

        DB::statement("ALTER TABLE `drms_expense_reports`
            MODIFY COLUMN `category` enum('toll','parkir','bbm','cuci_mobil') NOT NULL");
    }
};
