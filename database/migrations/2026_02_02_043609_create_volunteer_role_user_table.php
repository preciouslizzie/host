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
        Schema::create('volunteer_role_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('volunteer_role_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            // Prevent duplicate applications
            $table->unique(['user_id', 'volunteer_role_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('volunteer_role_user');
    }
};
