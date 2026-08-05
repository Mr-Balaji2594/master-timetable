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
        Schema::table('workload', function (Blueprint $table) {
            $table->string('sem_mode')->nullable()->after('year');
            $table->unsignedBigInteger('department_id')->nullable()->after('sem_mode');
        });
    }

    public function down(): void
    {
        Schema::table('workload', function (Blueprint $table) {
            $table->dropColumn(['sem_mode', 'department_id']);
        });
    }
};
