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
        if (!Schema::hasColumn('whatsapp_links', 'role_id')) {
            Schema::table('whatsapp_links', function (Blueprint $table) {
                $table->foreignId('role_id')
                    ->nullable()
                    ->after('link')
                    ->constrained('volunteer_roles')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('whatsapp_links', 'role_id')) {
            Schema::table('whatsapp_links', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
                $table->dropColumn('role_id');
            });
        }
    }
};
