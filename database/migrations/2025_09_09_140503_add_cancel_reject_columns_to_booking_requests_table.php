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
        if (!Schema::hasColumn('booking_requests', 'cancelled_by')) {
            Schema::table('booking_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('cancelled_by')->nullable()->after('status');
                $table->string('cancelled_by_role')->nullable()->after('cancelled_by');
                $table->text('cancel_reason')->nullable()->after('cancelled_by_role');
                
                $table->foreign('cancelled_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_requests', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['rejected_by']);
    
            $table->dropColumn([
                'cancelled_by',
                'cancelled_by_role',
                'cancel_reason',
            ]);
        });
    }
};
