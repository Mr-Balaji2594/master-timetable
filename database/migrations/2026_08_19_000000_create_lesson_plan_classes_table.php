<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('lesson_plan_classes')) {
            Schema::create('lesson_plan_classes', function (Blueprint $table) {
                $table->id();
                $table->integer('lesson_plan_id');
                $table->integer('class_id');

                $table->unique(['lesson_plan_id', 'class_id']);

                $table->foreign('lesson_plan_id')->references('id')->on('lesson_plans')->onDelete('cascade');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_plan_classes');
    }
};