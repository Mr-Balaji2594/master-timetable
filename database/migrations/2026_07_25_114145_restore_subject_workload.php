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
        Schema::create('workload', function (Blueprint $table) {
            $table->id();
            $table->string('year');
            $table->string('sem_mode')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('subject_name');
            $table->decimal('total_hours', 5, 1)->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workload');
    }
};
