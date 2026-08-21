<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('classes', 'program_type')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->string('program_type', 5)->default('UG')->after('department_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('program_type');
        });
    }
};
