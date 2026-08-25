<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'time')) {
                $table->dropColumn('time');
            }
            $table->time('start_time')->nullable()->after('due_date');
            $table->time('due_time')->nullable()->after('start_time');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'due_time']);
            $table->time('time')->nullable()->after('due_date');
        });
    }
};
