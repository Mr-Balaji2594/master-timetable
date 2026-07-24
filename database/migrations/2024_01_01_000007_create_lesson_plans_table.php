<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lesson_plans')) {
            Schema::create('lesson_plans', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('subject_id');
                $table->tinyInteger('day');
                $table->tinyInteger('period');
                $table->string('semester', 10)->nullable();
                $table->string('topic', 255);
                $table->text('description')->nullable();
                $table->string('unit', 100)->nullable();
                $table->date('plan_date')->nullable();
                $table->string('status', 30)->default('pending_hod');
                $table->unsignedBigInteger('hod_approved_by')->nullable();
                $table->timestamp('hod_approved_at')->nullable();
                $table->unsignedBigInteger('principal_approved_by')->nullable();
                $table->timestamp('principal_approved_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plans');
    }
};
