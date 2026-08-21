<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('classes', 'block')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->string('block', 20)->nullable()->after('year');
                $table->string('floor', 20)->nullable()->after('block');
            });
        }
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['block', 'floor']);
        });
    }
};
