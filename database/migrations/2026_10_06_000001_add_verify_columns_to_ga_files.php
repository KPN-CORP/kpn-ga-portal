<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ga_files', function (Blueprint $table) {
            // Kapan agent TERAKHIR meng-hash ulang file Local (syarat penghapusan Cloud)
            $table->timestamp('local_rechecked_at')->nullable()->after('local_verified_at');
            // Diisi bila file Local hilang/rusak padahal Cloud sudah tidak punya salinan
            $table->string('local_problem', 255)->nullable()->after('local_rechecked_at');
            $table->timestamp('local_problem_at')->nullable()->after('local_problem');

            $table->index(['local_exists', 'local_rechecked_at'], 'ga_files_recheck_idx');
        });
    }

    public function down(): void
    {
        Schema::table('ga_files', function (Blueprint $table) {
            $table->dropIndex('ga_files_recheck_idx');
            $table->dropColumn(['local_rechecked_at', 'local_problem', 'local_problem_at']);
        });
    }
};
