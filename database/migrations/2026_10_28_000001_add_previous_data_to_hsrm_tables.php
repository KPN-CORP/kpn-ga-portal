<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hsrm_certificates', function (Blueprint $table) {
            $table->json('previous_data')->nullable()->after('old_attachments');
        });
        Schema::table('hsrm_equipments', function (Blueprint $table) {
            $table->json('previous_data')->nullable()->after('old_attachments');
        });
    }

    public function down(): void
    {
        Schema::table('hsrm_certificates', function (Blueprint $table) {
            $table->dropColumn('previous_data');
        });
        Schema::table('hsrm_equipments', function (Blueprint $table) {
            $table->dropColumn('previous_data');
        });
    }
};
