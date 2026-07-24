<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('classes')) {
            Schema::create('classes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50);
                $table->unsignedBigInteger('department_id');
                $table->integer('batch_year');
                $table->string('year', 5)->nullable();
                $table->string('section', 5)->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
