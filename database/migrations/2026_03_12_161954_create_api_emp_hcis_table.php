<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('api_emp_hcis', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 20)->unique();
            $table->string('fullname');
            $table->string('email')->nullable();
            $table->string('group_company')->nullable();
            $table->string('office_area')->nullable();
            $table->string('manager_l1_id', 20)->nullable();
            $table->string('manager_l2_id', 20)->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('manager_l1_id');
            $table->index('manager_l2_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('api_emp_hcis');
    }
};