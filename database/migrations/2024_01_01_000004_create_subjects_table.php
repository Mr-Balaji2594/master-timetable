<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('code', 20)->unique();
                $table->unsignedBigInteger('department_id');
                $table->integer('credits')->default(0);
                $table->integer('lecture_hours_per_week')->default(0);
                $table->string('year', 5)->nullable();
                $table->integer('sem')->nullable();
                $table->string('sem_mode', 10)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
