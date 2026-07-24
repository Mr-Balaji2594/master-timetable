<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('common_paper_allocations')) {
            Schema::create('common_paper_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('class_id');
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
                $table->unique(['subject_id', 'class_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('common_paper_allocations');
    }
};
