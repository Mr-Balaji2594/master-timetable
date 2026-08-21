<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('earned_leave_credits')) {
            Schema::create('earned_leave_credits', function (Blueprint $table) {
                $table->id();
                $table->integer('employee_id');
                $table->date('credit_date');
                $table->integer('consecutive_days')->default(22);
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
                $table->unique(['employee_id', 'credit_date'], 'uniq_emp_credit_date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('earned_leave_credits');
    }
};
