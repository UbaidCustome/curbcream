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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('passenger_name'); // John Smith
            $table->text('location'); // 2972 Westheimer Rd...
            $table->enum('request_type', ['Schedule', 'Choose','Request']); // Request Type
            $table->date('ride_date'); // 12/1/2023
            $table->time('ride_time'); // 09:00 AM
            $table->integer('distance'); // 21 Miles
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('driver_id')->constrained('users')->onDelete('cascade');            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
