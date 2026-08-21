<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetable', function (Blueprint $table) {
            $table->string('status', 30)->default('approved')->after('room_no');
            $table->unsignedBigInteger('hod_approved_by')->nullable()->after('status');
            $table->timestamp('hod_approved_at')->nullable()->after('hod_approved_by');
            $table->unsignedBigInteger('principal_approved_by')->nullable()->after('hod_approved_at');
            $table->timestamp('principal_approved_at')->nullable()->after('principal_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('timetable', function (Blueprint $table) {
            $table->dropColumn(['status', 'hod_approved_by', 'hod_approved_at', 'principal_approved_by', 'principal_approved_at']);
        });
    }
};