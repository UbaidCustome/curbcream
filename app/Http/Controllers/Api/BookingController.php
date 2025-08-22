<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function getScheduledBookings()
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();
        $today = Carbon::now()->format('Y-m-d');

        if ($user->role == 'driver') {
            $bookings = Booking::where('driver_id', $user->id)
                        ->where('ride_date', '>=', $today)
                        ->where('request_type', 'Schedule')
                        ->with('user') // passenger data
                        ->orderBy('ride_date', 'desc')
                        ->get()
                        ->map(function ($booking) {
                            return [
                                'id' => $booking->id,
                                'passenger_name' => $booking->passenger_name,
                                'location' => $booking->location,
                                'ride_date' => $booking->ride_date,
                                'ride_time' => $booking->ride_time,
                                'distance' => $booking->distance,
                                'status' => $booking->status,
                                'request_type' => $booking->request_type,
                                // Passenger info for driver screen
                                'passenger' => [
                                    'id' => $booking->user->id,
                                    'name' => $booking->user->name,
                                    'avatar' => $booking->user->avatar ?? null,
                                ]
                            ];
                        });
        } else {
            $bookings = Booking::where('user_id', $user->id)
                        ->where('ride_date', '>=', $today)
                        ->where('request_type', 'Schedule')
                        ->with('driver') // driver data
                        ->orderBy('ride_date', 'desc')
                        ->get()
                        ->map(function ($booking) {
                            return [
                                'id' => $booking->id,
                                'location' => $booking->location,
                                'ride_date' => $booking->ride_date,
                                'ride_time' => $booking->ride_time,
                                'distance' => $booking->distance,
                                'status' => $booking->status,
                                'request_type' => $booking->request_type,
                                'amount' => $booking->amount ?? null, // detail screen field
                                // Driver info for passenger screen
                                'driver' => [
                                    'id' => $booking->driver->id,
                                    'name' => $booking->driver->name,
                                    'avatar' => $booking->driver->avatar ?? null,
                                    'reviews_count' => $booking->driver->reviews()->count(),
                                    'avg_rating' => round($booking->driver->reviews()->avg('rating'), 1),
                                ]
                            ];
                        });
        }

        return response()->json([
            'status' => 1,
            'message' => 'Scheduled bookings retrieved successfully.',
            'data' => $bookings
        ]);
    }

    public function getBookingHistory()
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user = Auth::user();
        $today = Carbon::now()->format('Y-m-d');

        if ($user->role == 'driver') {
            $bookings = Booking::where('driver_id', $user->id)
                        ->where('ride_date', '<', $today)
                        ->with('user') // passenger data
                        ->orderBy('ride_date', 'desc')
                        ->get()
                        ->map(function ($booking) {
                            return [
                                'id' => $booking->id,
                                'passenger_name' => $booking->passenger_name,
                                'location' => $booking->location,
                                'ride_date' => $booking->ride_date,
                                'ride_time' => $booking->ride_time,
                                'distance' => $booking->distance,
                                'status' => $booking->status,
                                'request_type' => $booking->request_type,
                                'amount' => $booking->amount ?? null,
                                // Passenger info for driver screen
                                'passenger' => [
                                    'id' => $booking->user->id,
                                    'name' => $booking->user->name,
                                    'avatar' => $booking->user->avatar ?? null,
                                ]
                            ];
                        });
        } else {
            $bookings = Booking::where('user_id', $user->id)
                        ->where('ride_date', '<', $today)
                        ->with('driver') // driver data
                        ->orderBy('ride_date', 'desc')
                        ->get()
                        ->map(function ($booking) {
                            return [
                                'id' => $booking->id,
                                'location' => $booking->location,
                                'ride_date' => $booking->ride_date,
                                'ride_time' => $booking->ride_time,
                                'distance' => $booking->distance,
                                'status' => $booking->status,
                                'request_type' => $booking->request_type,
                                'amount' => $booking->amount ?? null,
                                // Driver info for passenger screen
                                'driver' => [
                                    'id' => $booking->driver->id,
                                    'name' => $booking->driver->name,
                                    'avatar' => $booking->driver->avatar ?? null,
                                    'reviews_count' => $booking->driver->reviews()->count(),
                                    'avg_rating' => round($booking->driver->reviews()->avg('rating'), 1),
                                ]
                            ];
                        });
        }

        return response()->json([
            'status' => 1,
            'message' => 'Booking history retrieved successfully.',
            'data' => $bookings
        ]);
    }


}
