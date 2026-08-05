<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('workload');

        Schema::create('workload', function (Blueprint $table) {
            $table->id();
            $table->string('year');
            $table->string('subject_name');
            $table->decimal('total_hours', 5, 1)->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workload');

        Schema::create('workload', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->decimal('total_hours', 5, 1)->default(0);
            $table->decimal('period_week', 5, 1)->default(0);
            $table->date('computed_date');
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }
};
