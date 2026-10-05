<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ga_files', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 20);                 // public / private
            $table->string('path', 500);                // path relatif di disk
            $table->char('path_hash', 40);              // sha1(path) untuk unique index
            $table->unsignedBigInteger('size')->default(0);
            $table->string('mime', 100)->nullable();
            $table->char('sha256', 64);                 // hash versi terbaru (master)
            $table->unsignedInteger('version')->default(1);

            $table->boolean('local_exists')->default(false);
            $table->char('local_sha256', 64)->nullable();
            $table->timestamp('local_verified_at')->nullable();

            $table->boolean('cloud_exists')->default(true);
            $table->timestamp('cloud_uploaded_at')->nullable();
            $table->timestamp('cloud_delete_after')->nullable();

            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();

            $table->unique(['disk', 'path_hash'], 'ga_files_disk_path_unique');
            $table->index(['cloud_exists', 'cloud_delete_after'], 'ga_files_cloud_expiry_idx');
            $table->index(['local_exists', 'cloud_exists'], 'ga_files_sync_idx');
        });

        Schema::create('ga_file_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained('ga_files')->cascadeOnDelete();
            $table->string('status', 12)->default('PENDING');   // PENDING, PROCESSING, COMPLETED, FAILED
            $table->unsignedBigInteger('requested_by')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('lease_until')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'lease_until'], 'ga_far_status_lease_idx');
            $table->index(['file_id', 'status'], 'ga_far_file_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ga_file_access_requests');
        Schema::dropIfExists('ga_files');
    }
};
