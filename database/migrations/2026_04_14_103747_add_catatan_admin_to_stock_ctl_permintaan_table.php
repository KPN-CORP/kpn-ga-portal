<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCatatanAdminToStockCtlPermintaanTable extends Migration
{
    public function up()
    {
        Schema::table('stock_ctl_permintaan', function (Blueprint $table) {
            $table->text('catatan_admin')->nullable()->after('alasan_tolak');
        });
    }

    public function down()
    {
        Schema::table('stock_ctl_permintaan', function (Blueprint $table) {
            $table->dropColumn('catatan_admin');
        });
    }
}