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
            // Add 'password_reset_token' and 'password_reset_expires_at' fields after the 'password' column
            $table->string('password_reset_token', 60)->nullable()->after('password');
            $table->timestamp('password_reset_expires_at')->nullable()->after('password_reset_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove the fields if rolling back
            $table->dropColumn('password_reset_token');
            $table->dropColumn('password_reset_expires_at');
        });;
    }
};
