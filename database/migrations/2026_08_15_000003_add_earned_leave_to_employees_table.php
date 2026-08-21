<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->integer('earned_leave_limit')->default(0)->after('deputation_limit');
            $table->integer('earned_leave_availed')->default(0)->after('earned_leave_limit');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['earned_leave_limit', 'earned_leave_availed']);
        });
    }
};
