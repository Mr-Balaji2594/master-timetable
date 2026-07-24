<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('employee_subjects')) {
            Schema::create('employee_subjects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->unsignedBigInteger('subject_id');

                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_subjects');
    }
};
