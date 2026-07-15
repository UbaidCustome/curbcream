<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        Schema::create('service_regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('US');
            $table->decimal('center_lat', 10, 7)->nullable();
            $table->decimal('center_lng', 10, 7)->nullable();
            $table->unsignedInteger('radius_km')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('platform_settings')->insert([
            ['key' => 'max_service_distance_km', 'value' => '10', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'timezone', 'value' => 'America/Chicago', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'region_label', 'value' => 'Texas, USA', 'created_at' => $now, 'updated_at' => $now],
            ['key' => 'distance_unit', 'value' => 'km', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('service_regions')->insert([
            [
                'name' => 'Austin Metro',
                'city' => 'Austin',
                'state' => 'TX',
                'country' => 'US',
                'center_lat' => 30.2672000,
                'center_lng' => -97.7431000,
                'radius_km' => 25,
                'is_enabled' => true,
                'notes' => 'Downtown and surrounding suburbs',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Dallas Midtown',
                'city' => 'Dallas',
                'state' => 'TX',
                'country' => 'US',
                'center_lat' => 32.7767000,
                'center_lng' => -96.7970000,
                'radius_km' => 30,
                'is_enabled' => true,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Houston Heights',
                'city' => 'Houston',
                'state' => 'TX',
                'country' => 'US',
                'center_lat' => 29.7604000,
                'center_lng' => -95.3698000,
                'radius_km' => 35,
                'is_enabled' => false,
                'notes' => 'Temporarily disabled for testing',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('service_regions');
        Schema::dropIfExists('platform_settings');
    }
};
