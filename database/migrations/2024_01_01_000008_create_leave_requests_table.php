<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('employee_id');
                $table->date('leave_date');
                $table->date('due_date')->nullable();
                $table->string('nature', 50)->default('casual');
                $table->integer('days')->default(1);
                $table->text('reason')->nullable();
                $table->string('status', 30)->default('pending_hod');
                $table->unsignedBigInteger('hod_approved_by')->nullable();
                $table->timestamp('hod_approved_at')->nullable();
                $table->unsignedBigInteger('principal_approved_by')->nullable();
                $table->timestamp('principal_approved_at')->nullable();
                $table->timestamp('applied_at')->useCurrent();

                $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
