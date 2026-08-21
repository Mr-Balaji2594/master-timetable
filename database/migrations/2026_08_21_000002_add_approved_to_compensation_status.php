<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('compensation')) {
            Schema::table('compensation', function (Blueprint $table) {
                $table->enum('status', ['pending', 'completed', 'approved', 'cancelled'])->default('pending')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('compensation')) {
            Schema::table('compensation', function (Blueprint $table) {
                $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending')->change();
            });
        }
    }
};