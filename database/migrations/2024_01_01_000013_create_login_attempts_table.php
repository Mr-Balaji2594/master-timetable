<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('login_attempts')) {
            Schema::create('login_attempts', function (Blueprint $table) {
                $table->id();
                $table->string('emp_id', 20);
                $table->string('ip_address', 45)->nullable();
                $table->boolean('success')->default(false);
                $table->timestamp('attempted_at')->useCurrent();

                $table->index(['emp_id', 'attempted_at']);
                $table->index(['ip_address', 'attempted_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('login_attempts');
    }
};
