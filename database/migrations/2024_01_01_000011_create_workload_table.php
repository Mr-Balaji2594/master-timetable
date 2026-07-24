<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('workload')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('workload');
    }
};
