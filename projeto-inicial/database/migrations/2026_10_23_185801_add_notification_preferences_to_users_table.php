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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('wants_email')->default(true);
            $table->boolean('wants_push')->default(false);
            $table->boolean('is_operations')->default(false);
            $table->string('slack_channel')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wants_email', 'wants_push', 'is_operations', 'slack_channel']);
        });
    }
};
