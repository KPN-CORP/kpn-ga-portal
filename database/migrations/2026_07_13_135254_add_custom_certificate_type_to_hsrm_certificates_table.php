<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('hsrm_certificates', function (Blueprint $table) {
            if (!Schema::hasColumn('hsrm_certificates', 'custom_certificate_type')) {
                $table->string('custom_certificate_type')->nullable()->after('certificate_type_id');
            }
        });
    }

    public function down()
    {
        Schema::table('hsrm_certificates', function (Blueprint $table) {
            $table->dropColumn('custom_certificate_type');
        });
    }
};