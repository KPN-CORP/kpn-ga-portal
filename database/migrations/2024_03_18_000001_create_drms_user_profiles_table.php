<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('drms_user_profiles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedInteger('business_unit_id')->nullable();
            $table->string('unit')->nullable();
            $table->string('area')->nullable();
            $table->unsignedBigInteger('approver_user_id')->nullable();
            $table->boolean('is_approver')->default(false);
            $table->boolean('is_drms_user')->default(false);
            $table->boolean('is_drms_admin')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('business_unit_id')->references('id_bisnis_unit')->on('tb_bisnis_unit')->onDelete('set null');
            $table->foreign('approver_user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('drms_user_profiles');
    }
};