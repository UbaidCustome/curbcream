<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Dispute;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $customers = User::where('role', 'user')->orderBy('id')->get();
        $providers = User::where('role', 'driver')->where('document_status', 'approved')->orderBy('id')->get();

        if ($customers->isEmpty() || $providers->isEmpty()) {
            $this->command?->warn('Skipping BookingSeeder: customers or approved providers missing.');
            return;
        }

        Dispute::query()->delete();
        Review::query()->delete();
        Booking::query()->delete();
        BookingRequest::query()->delete();

        $statuses = ['Pending', 'Accepted', 'On Going', 'Completed', 'Rejected', 'Cancelled'];
        $requestTypes = ['Schedule', 'Choose', 'Request'];
        $locations = [
            'Zilker Park, Austin, TX',
            'Katy Trail, Dallas, TX',
            'Buffalo Bayou Park, Houston, TX',
            'The Pearl, San Antonio, TX',
            'Legacy West, Plano, TX',
            'Sundance Square, Fort Worth, TX',
            'Stewart Beach, Galveston, TX',
            'South Congress Ave, Austin, TX',
        ];

        $bookings = [];

        for ($i = 1; $i <= 40; $i++) {
            $customer = $customers[($i - 1) % $customers->count()];
            $provider = $providers[($i - 1) % $providers->count()];
            $status = $statuses[($i - 1) % count($statuses)];
            $isPast = in_array($status, ['Completed', 'Rejected', 'Cancelled'], true);
            $location = $locations[($i - 1) % count($locations)];

            $bookings[] = [
                'user_id' => $customer->id,
                'driver_id' => $provider->id,
                'passenger_name' => $customer->name,
                'location' => $location,
                'request_type' => $requestTypes[($i - 1) % count($requestTypes)],
                'ride_date' => $isPast
                    ? Carbon::now()->subDays(rand(1, 25))->format('Y-m-d')
                    : Carbon::now()->addDays(rand(0, 10))->format('Y-m-d'),
                'ride_time' => Carbon::createFromTime(rand(10, 20), [0, 15, 30, 45][rand(0, 3)], 0)->format('H:i:s'),
                'distance' => rand(2, 28),
                'status' => $status,
                'amount' => rand(8, 45) + (rand(0, 99) / 100),
                'created_at' => now()->subDays(rand(0, 30)),
                'updated_at' => now(),
            ];
        }

        Booking::insert($bookings);

        $createdBookings = Booking::orderBy('id')->get();

        foreach ($createdBookings->where('status', 'Completed')->take(12) as $index => $booking) {
            Review::create([
                'user_id' => $booking->user_id,
                'driver_id' => $booking->driver_id,
                'rating' => rand(3, 5),
                'review' => [
                    'Great ice cream and friendly service!',
                    'Arrived on time, kids loved it.',
                    'Flavors were fresh and tasty.',
                    'Good experience overall.',
                ][$index % 4],
            ]);
        }

        $requestStatuses = ['Pending', 'Accepted', 'On Going', 'Completed', 'Rejected'];
        $disputeStatuses = ['pending', 'on-going', 'high-priority', 'resolved', 'rejected'];

        foreach ($createdBookings->take(10) as $index => $booking) {
            $request = BookingRequest::create([
                'user_id' => $booking->user_id,
                'driver_id' => $booking->driver_id,
                'request_type' => $booking->request_type,
                'status' => $requestStatuses[$index % count($requestStatuses)],
                'ride_time' => $booking->ride_time,
                'lat' => 30.2500 + ($index * 0.01),
                'lng' => -97.7500 - ($index * 0.01),
                'location' => $booking->location,
                'special_instruction' => 'Seeded request for testing',
                'distance' => $booking->distance,
                'amount' => $booking->amount,
            ]);

            if ($index < 8) {
                Dispute::create([
                    'user_id' => $booking->user_id,
                    'driver_id' => $booking->driver_id,
                    'booking_id' => $request->id,
                    'reason' => ['Late arrival', 'Wrong order', 'Payment issue', 'Rude behavior'][$index % 4],
                    'description' => 'Auto-seeded dispute for admin testing.',
                    'status' => $disputeStatuses[$index % count($disputeStatuses)],
                ]);
            }
        }
    }
}
