<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('subjects', 'is_common')) {
            Schema::table('subjects', function (Blueprint $table) {
                $table->boolean('is_common')->default(false)->after('lecture_hours_per_week');
            });
        }

        DB::table('departments')->updateOrInsert(
            ['code' => 'LAN'],
            ['name' => 'Languages', 'code' => 'LAN']
        );
        $langDeptId = DB::table('departments')->where('code', 'LAN')->value('id');

        $commonSubjects = [
            ['name' => 'Tamil', 'code' => 'TAM01', 'department_id' => $langDeptId, 'is_common' => true],
            ['name' => 'English', 'code' => 'ENG01', 'department_id' => $langDeptId, 'is_common' => true],
        ];

        foreach ($commonSubjects as $subject) {
            DB::table('subjects')->updateOrInsert(
                ['name' => $subject['name'], 'code' => $subject['code']],
                $subject
            );
        }

        DB::table('subjects')->whereIn('name', ['EVS', 'Naan Mudhalvan'])->update(['is_common' => true]);
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('is_common');
        });
    }
};
