<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('substitution_duties', function (Blueprint $table) {
            if (!Schema::hasColumn('substitution_duties', 'compensated')) {
                $table->boolean('compensated')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('substitution_duties', function (Blueprint $table) {
            $table->dropColumn('compensated');
        });
    }
};