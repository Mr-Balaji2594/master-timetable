<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('departments')) {
            Schema::create('departments', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('code', 20)->unique();
                $table->unsignedBigInteger('hod_id')->nullable();
                $table->string('branch_code', 20)->nullable();
                $table->integer('staff_count')->default(0);
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
    }
};
