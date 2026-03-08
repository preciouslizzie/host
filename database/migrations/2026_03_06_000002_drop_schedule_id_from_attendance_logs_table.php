<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('attendance_logs', 'schedule_id')) {
            Schema::table('attendance_logs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('schedule_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('attendance_logs', 'schedule_id')) {
            Schema::table('attendance_logs', function (Blueprint $table) {
                $table->foreignId('schedule_id')->constrained();
            });
        }
    }
};
