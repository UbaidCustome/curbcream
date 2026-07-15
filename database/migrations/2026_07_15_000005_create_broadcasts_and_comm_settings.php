<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('channel', ['notification', 'email'])->default('notification');
            $table->enum('category', [
                'service_update',
                'feature',
                'promo',
                'policy',
                'subscription_expiry',
                'job_status',
                'review_reminder',
                'account_update',
                'custom',
            ])->default('custom');
            $table->enum('audience', ['all', 'customers', 'providers'])->default('all');
            $table->string('title');
            $table->string('subject')->nullable();
            $table->text('message');
            $table->unsignedInteger('recipients_count')->default(0);
            $table->enum('status', ['sent', 'failed', 'partial'])->default('sent');
            $table->text('meta')->nullable();
            $table->timestamps();
        });

        $now = now();
        $settings = [
            'auto_notify_subscription_expiry' => '1',
            'auto_notify_job_status' => '1',
            'auto_notify_customer_reviews' => '1',
            'auto_email_account_updates' => '1',
            'auto_email_promotions' => '0',
        ];

        foreach ($settings as $key => $value) {
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('broadcasts');

        DB::table('platform_settings')->whereIn('key', [
            'auto_notify_subscription_expiry',
            'auto_notify_job_status',
            'auto_notify_customer_reviews',
            'auto_email_account_updates',
            'auto_email_promotions',
        ])->delete();
    }
};
