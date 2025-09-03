<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->foreignId('driver_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->enum('request_type', ['Schedule', 'Choose', 'Request']);

            $table->enum('status', ['Pending', 'Accepted', 'On Going', 'Completed', 'Rejected'])->default('Pending');

            $table->time('ride_time')->nullable();

            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->text('location')->nullable();

            $table->text('special_instruction')->nullable();

            $table->integer('distance')->nullable();
            $table->decimal('amount', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_requests');
    }
};

