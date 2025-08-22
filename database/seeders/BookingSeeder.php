<?php

namespace Database\Seeders;

use App\Models\Booking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Pehle existing bookings clear
        Booking::truncate();

        $bookings = [];

        // 3 Bookings with user_id=8 and driver_id=9
        for ($i = 1; $i <= 3; $i++) {
            $bookings[] = [
                'user_id'        => 8,
                'driver_id'      => 9,
                'passenger_name' => 'User ' . $i,
                'location'       => '2972 Westheimer Rd. Santa Ana, Illinois 85486',
                'request_type'   => $this->getRequestType($i),
                'ride_date'      => $i % 2 == 0
                                    ? Carbon::now()->subDays($i)->format('Y-m-d') // past ride
                                    : Carbon::now()->addDays($i)->format('Y-m-d'), // future ride
                'ride_time'      => Carbon::createFromTime(rand(7, 22), 0, 0)->format('H:i:s'), // random hour
                'distance'       => rand(10, 50),
                'status'         => $this->getStatus($i),
                'amount'         => rand(200, 1000), // random fare
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        // 2 Bookings with user_id=1 and driver_id=5
        for ($i = 4; $i <= 5; $i++) {
            $bookings[] = [
                'user_id'        => 1,
                'driver_id'      => 5,
                'passenger_name' => 'User ' . $i,
                'location'       => '2972 Westheimer Rd. Santa Ana, Illinois 85486',
                'request_type'   => $this->getRequestType($i),
                'ride_date'      => $i % 2 == 0
                                    ? Carbon::now()->subDays($i)->format('Y-m-d')
                                    : Carbon::now()->addDays($i)->format('Y-m-d'),
                'ride_time'      => Carbon::createFromTime(rand(7, 22), 0, 0)->format('H:i:s'),
                'distance'       => rand(10, 50),
                'status'         => $this->getStatus($i),
                'amount'         => rand(200, 1000),
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        Booking::insert($bookings);
    }

    private function getRequestType($index)
    {
        $types = ['Schedule', 'Choose', 'Request'];
        return $types[($index - 1) % 3];
    }

    private function getStatus($index)
    {
        $statuses = ['Pending', 'Accepted', 'Rejected', 'Completed'];
        return $statuses[$index % count($statuses)];
    }
}
