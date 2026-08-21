<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['permission_limit', 'permission_availed']);
            $table->integer('early_permission_limit')->default(2)->after('onduty_leave_limit');
            $table->decimal('early_permission_availed', 5, 1)->default(0)->after('early_permission_limit');
            $table->integer('late_permission_limit')->default(2)->after('early_permission_availed');
            $table->decimal('late_permission_availed', 5, 1)->default(0)->after('late_permission_limit');
            $table->decimal('casual_leave_availed', 5, 1)->default(0)->change();
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->decimal('days', 5, 1)->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['early_permission_limit', 'early_permission_availed', 'late_permission_limit', 'late_permission_availed']);
            $table->integer('permission_limit')->default(5)->after('onduty_leave_limit');
            $table->integer('permission_availed')->default(0)->after('onduty_leave_availed');
            $table->integer('casual_leave_availed')->default(0)->change();
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->integer('days')->default(1)->change();
        });
    }
};
