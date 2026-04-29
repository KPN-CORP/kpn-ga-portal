<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('track_r_recipients', function (Blueprint $table) {
            $table->enum('status', ['dikirim', 'diterima', 'ditolak', 'diteruskan'])
                  ->default('dikirim')
                  ->after('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('track_r_recipients', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};