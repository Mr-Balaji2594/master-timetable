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
        Schema::table('compensations', function (Blueprint $table) {
            $table->date('compensation_date')->nullable()->change();
            $table->tinyInteger('compensation_period')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('compensations', function (Blueprint $table) {
            $table->date('compensation_date')->nullable(false)->change();
            $table->tinyInteger('compensation_period')->nullable(false)->change();
        });
    }
};
