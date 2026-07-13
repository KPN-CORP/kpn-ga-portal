<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('hsrm_equipments', function (Blueprint $table) {
            if (!Schema::hasColumn('hsrm_equipments', 'custom_equipment_type')) {
                $table->string('custom_equipment_type')->nullable()->after('equipment_type_id');
            }
        });
    }

    public function down()
    {
        Schema::table('hsrm_equipments', function (Blueprint $table) {
            $table->dropColumn('custom_equipment_type');
        });
    }
};