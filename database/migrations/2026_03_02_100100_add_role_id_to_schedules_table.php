<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('schedules', 'role_id')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->foreignId('role_id')
                    ->after('user_id')
                    ->constrained('volunteer_roles');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('schedules', 'role_id')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            });
        }
    }
};
