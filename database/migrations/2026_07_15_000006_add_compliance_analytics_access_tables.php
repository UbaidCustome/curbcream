<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'is_flagged')) {
                $table->boolean('is_flagged')->default(false)->after('review');
            }
            if (!Schema::hasColumn('reviews', 'moderation_status')) {
                $table->enum('moderation_status', ['visible', 'removed'])->default('visible')->after('is_flagged');
            }
            if (!Schema::hasColumn('reviews', 'admin_response')) {
                $table->text('admin_response')->nullable()->after('moderation_status');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'admin_access_level')) {
                $table->enum('admin_access_level', ['super_admin', 'support', 'moderator'])
                    ->nullable()
                    ->after('role');
            }
        });

        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->nullable();
            $table->enum('guard', ['admin', 'api'])->default('admin');
            $table->enum('status', ['success', 'failed', 'unauthorized'])->default('success');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('message')->nullable();
            $table->timestamps();
        });

        $now = now();
        $policies = [
            ['type' => 'terms', 'description' => 'Terms & Conditions for CurbCream platform.'],
            ['type' => 'privacy', 'description' => 'Privacy Policy for CurbCream platform.'],
            ['type' => 'refund', 'description' => 'Refund Policy for CurbCream platform.'],
        ];

        foreach ($policies as $policy) {
            $exists = DB::table('contents')->where('type', $policy['type'])->exists();
            if (!$exists) {
                DB::table('contents')->insert([
                    'type' => $policy['type'],
                    'description' => $policy['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('users')
            ->where('role', 'admin')
            ->whereNull('admin_access_level')
            ->update(['admin_access_level' => 'super_admin']);
    }

    public function down(): void
    {
        Schema::dropIfExists('login_activities');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'admin_access_level')) {
                $table->dropColumn('admin_access_level');
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            foreach (['is_flagged', 'moderation_status', 'admin_response'] as $column) {
                if (Schema::hasColumn('reviews', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
