<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('substitution_duties')) {
            Schema::create('substitution_duties', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('original_employee_id');
                $table->unsignedBigInteger('substitute_employee_id');
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('subject_id');
                $table->tinyInteger('day_of_week');
                $table->tinyInteger('period_no');
                $table->date('leave_date');
                $table->string('status', 20)->default('assigned');
                $table->decimal('compensation_hours', 3, 1)->default(0);
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('original_employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->foreign('substitute_employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('substitution_duties');
    }
};
