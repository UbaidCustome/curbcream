<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'driver', 'admin') NOT NULL DEFAULT 'user'");

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'document_status')) {
                $table->enum('document_status', ['pending', 'approved', 'rejected', 'more_info'])
                    ->default('pending')
                    ->after('status');
            }
            if (!Schema::hasColumn('users', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('document_status');
            }
            if (!Schema::hasColumn('users', 'subscription_plan')) {
                $table->string('subscription_plan')->nullable()->after('admin_notes');
            }
            if (!Schema::hasColumn('users', 'subscription_status')) {
                $table->enum('subscription_status', ['none', 'active', 'expired'])
                    ->default('none')
                    ->after('subscription_plan');
            }
            if (!Schema::hasColumn('users', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable()->after('subscription_status');
            }
            if (!Schema::hasColumn('users', 'is_banned')) {
                $table->boolean('is_banned')->default(false)->after('subscription_expires_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'document_status',
                'admin_notes',
                'subscription_plan',
                'subscription_status',
                'subscription_expires_at',
                'is_banned',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user', 'driver') NOT NULL DEFAULT 'user'");
    }
};
