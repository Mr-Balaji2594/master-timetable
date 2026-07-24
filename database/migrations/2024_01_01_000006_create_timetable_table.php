<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('timetable')) {
            Schema::create('timetable', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('class_id');
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('employee_id');
                $table->tinyInteger('day_of_week')->comment('1=Monday to 6=Saturday');
                $table->tinyInteger('period_no')->comment('1 to 6');
                $table->string('semester', 10)->nullable();
                $table->string('room_no', 20)->nullable();
                $table->unsignedBigInteger('combined_group_id')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->unique(['class_id', 'day_of_week', 'period_no'], 'unique_slot');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable');
    }
};
