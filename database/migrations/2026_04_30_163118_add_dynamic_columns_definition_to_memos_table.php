<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('memos', function (Blueprint $table) {
            if (!Schema::hasColumn('memos', 'dynamic_columns_definition')) {
                $table->json('dynamic_columns_definition')->nullable()->after('business_unit');
            }
        });
    }

    public function down()
    {
        Schema::table('memos', function (Blueprint $table) {
            $table->dropColumn('dynamic_columns_definition');
        });
    }
};