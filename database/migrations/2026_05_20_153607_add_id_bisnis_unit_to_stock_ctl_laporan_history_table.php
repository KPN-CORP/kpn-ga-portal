<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stock_ctl_laporan_history', function (Blueprint $table) {
            $table->unsignedInteger('id_bisnis_unit')->nullable()->after('id_user');
        });
    }

    public function down()
    {
        Schema::table('stock_ctl_laporan_history', function (Blueprint $table) {
            $table->dropColumn('id_bisnis_unit');
        });
    }
};