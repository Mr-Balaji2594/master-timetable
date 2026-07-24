<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('emp_id', 20)->unique();
                $table->unsignedBigInteger('department_id');
                $table->string('name', 100);
                $table->string('designation', 50);
                $table->integer('total_leave_per_year')->default(12);
                $table->integer('casual_leave_limit')->default(12);
                $table->integer('medical_leave_limit')->default(10);
                $table->integer('onduty_leave_limit')->default(5);
                $table->integer('permission_limit')->default(5);
                $table->integer('deputation_limit')->default(5);
                $table->integer('casual_leave_availed')->default(0);
                $table->integer('medical_leave_availed')->default(0);
                $table->integer('onduty_leave_availed')->default(0);
                $table->integer('permission_availed')->default(0);
                $table->integer('deputation_availed')->default(0);
                $table->string('role', 20)->default('staff');
                $table->string('phone', 20)->nullable();
                $table->string('email', 100)->nullable();
                $table->string('password', 255);
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
