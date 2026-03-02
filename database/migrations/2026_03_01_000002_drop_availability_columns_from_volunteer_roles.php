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
        Schema::table('volunteer_roles', function (Blueprint $table) {
            if (Schema::hasColumn('volunteer_roles', 'availability_required')) {
                $table->dropColumn('availability_required');
            }

            if (Schema::hasColumn('volunteer_roles', 'availability')) {
                $table->dropColumn('availability');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('volunteer_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('volunteer_roles', 'availability_required')) {
                $table->string('availability_required')->nullable();
            }

            if (!Schema::hasColumn('volunteer_roles', 'availability')) {
                $table->string('availability')->nullable();
            }
        });
    }
};
