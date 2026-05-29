<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tb_transaksi', function (Blueprint $table) {
            $table->text('maps_asal')->nullable()->after('alamat_asal');
            $table->text('maps_tujuan')->nullable()->after('alamat_tujuan');
        });
    }

    public function down()
    {
        Schema::table('tb_transaksi', function (Blueprint $table) {
            $table->dropColumn(['maps_asal', 'maps_tujuan']);
        });
    }
};