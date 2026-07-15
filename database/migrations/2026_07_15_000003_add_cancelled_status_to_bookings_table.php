<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bookings')) {
            DB::statement("ALTER TABLE bookings MODIFY status ENUM('Pending','Accepted','On Going','Completed','Rejected','Cancelled') NOT NULL DEFAULT 'Pending'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            DB::statement("ALTER TABLE bookings MODIFY status ENUM('Pending','Accepted','On Going','Completed','Rejected') NOT NULL DEFAULT 'Pending'");
        }
    }
};
