<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('employees', 'mode')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('mode', 20)->default('permanent')->after('designation');
            });
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('mode');
        });
    }
};
