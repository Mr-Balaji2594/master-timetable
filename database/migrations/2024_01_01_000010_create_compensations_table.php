<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('compensations')) {
            Schema::create('compensations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('substitute_employee_id');
                $table->unsignedBigInteger('original_employee_id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('subject_id');
                $table->tinyInteger('day_of_week');
                $table->tinyInteger('period_no');
                $table->date('leave_date');
                $table->date('compensation_date');
                $table->tinyInteger('compensation_period');
                $table->string('status', 20)->default('pending');
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('substitute_employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->foreign('original_employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('compensations');
    }
};
