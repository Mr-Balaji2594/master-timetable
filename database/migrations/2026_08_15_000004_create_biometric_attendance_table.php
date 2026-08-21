<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('biometric_attendance')) {
            Schema::create('biometric_attendance', function (Blueprint $table) {
                $table->id();
                $table->integer('employee_id');
                $table->date('attendance_date');
                $table->string('check_in', 8)->nullable();
                $table->string('check_out', 8)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->unique(['employee_id', 'attendance_date'], 'uniq_emp_attendance_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('biometric_attendance');
    }
};
