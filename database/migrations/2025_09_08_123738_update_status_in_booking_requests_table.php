<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE booking_requests MODIFY status ENUM('Pending','Accepted','Completed','Cancelled','On Going','Rejected') NOT NULL DEFAULT 'Pending'");
    }
    
    public function down()
    {
        DB::statement("ALTER TABLE booking_requests MODIFY status ENUM('Pending','Accepted','Completed','Cancelled','On Going','Rejected') NOT NULL DEFAULT 'Pending'");
    }
};
