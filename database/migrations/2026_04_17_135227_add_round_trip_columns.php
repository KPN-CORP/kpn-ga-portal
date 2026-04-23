<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('drms_requests', function (Blueprint $table) {
            $table->text('pickup_maps_link')->nullable()->after('pickup_location');
            $table->text('destination_maps_link')->nullable()->after('destination');
        });
    }

    public function down()
    {
        Schema::table('drms_requests', function (Blueprint $table) {
            $table->dropColumn(['pickup_maps_link', 'destination_maps_link']);
        });
    }
};