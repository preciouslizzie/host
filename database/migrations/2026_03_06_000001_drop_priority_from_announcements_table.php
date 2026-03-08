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
        if (Schema::hasColumn('announcements', 'priority')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropColumn('priority');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('announcements', 'priority')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            });
        }
    }
};
