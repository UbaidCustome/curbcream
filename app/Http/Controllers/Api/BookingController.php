<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BookingController extends Controller
{
    public function scheduleBooking(Request $request)
    {
        try {
            if (Auth::user()->role !== 'user') {
                return response()->json([
                    'status' => 0,
                    'message' => 'Only users can create bookings.',
                ], 403);
            }
            $request->validate([
                'ride_time' => 'required|date_format:H:i',
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
                'location' => 'nullable|string',
                'special_instruction' => 'nullable|string',
            ]);
    
            $now = Carbon::now();
            $rideDateTime = Carbon::today()->setTimeFromTimeString($request->ride_time);
    
            if ($rideDateTime->lessThanOrEqualTo($now)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Ride time must be later than the current time.',
                ], 422);
            }
    
            // if ($rideDateTime->lessThanOrEqualTo($now->copy()->addHour())) {
            //     return response()->json([
            //         'status' => 0,
            //         'message' => 'Scheduled rides must be booked at least 1 hour in advance.',
            //     ], 422);
            // }
    
            $radius = 5; // km
            $pickupLat = $request->lat;
            $pickupLng = $request->lng;
    
            $drivers = User::select(
                    'id',
                    'current_lat',
                    'current_lng',
                    DB::raw("CAST(ROUND(6371 * acos(
                        cos(radians($pickupLat)) 
                        * cos(radians(current_lat)) 
                        * cos(radians(current_lng) - radians($pickupLng)) 
                        + sin(radians($pickupLat)) 
                        * sin(radians(current_lat))
                    ), 2) AS DECIMAL(5,2)) AS distance")
                )
                ->where('role', 'driver')
                ->where('is_active', 1)
                ->having('distance', '<=', $radius)
                ->orderBy('distance', 'asc')
                ->get();
    
            if ($drivers->isEmpty()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'No drivers found. Please try later.',
                ], 404);
            }
    
            // ✅ Transaction start
            DB::beginTransaction();
    
            $booking = BookingRequest::create([
                'user_id' => Auth::user()->id,
                'request_type' => 'Schedule',
                'status' => 'Pending',
                'ride_time' => $request->ride_time,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'location' => $request->location,
                'special_instruction' => $request->special_instruction,
            ]);
    
            foreach ($drivers as $driver) {
                Notification::create([
                    'user_id' => Auth::id(),
                    'driver_id' => $driver->id,
                    'booking_id' => $booking->id,
                    'title' => 'New Booking Received',
                    'message' => (Auth::user()->name ?? Auth::user()->first_name.' '.Auth::user()->last_name) 
                                 . ' has requested a booking at ' . $request->ride_time,
                ]);
            }
    
            DB::commit(); // ✅ Transaction successful
            $bookingArray = $booking->toArray();
            $bookingArray['name'] = Auth::user()->name ?? Auth::user()->first_name.' '.Auth::user()->last_name;
            $bookingArray['avatar'] = Auth::user()->avatar ?? null;
    
            // ✅ Socket emit (transaction ke bahar)
            Http::withoutVerifying()->post(env('SOCKET_SERVER_URL').'/emit/new-schedule-booking', [
                'booking' => $bookingArray,
                'drivers' => $drivers,
            ]);
            return response()->json([
                'status' => 1,
                'message' => 'Schedule booking created successfully. Nearby drivers notified.',
                'data' => $bookingArray,
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack(); // ❌ Rollback if error
            return response()->json([
                'status' => 0,
                'message' => 'Failed to create booking.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function chooseTruckBooking(Request $request)
    {
        try {
            if (Auth::user()->role !== 'user') {
                return response()->json([
                    'status' => 0,
                    'message' => 'Only users can create bookings.',
                ], 403);
            }
    
            $request->validate([
                'driver_id' => 'required|exists:users,id',
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
            ]);
    
            DB::beginTransaction();
    
            // ✅ Create booking
            $booking = BookingRequest::create([
                'user_id' => Auth::id(),
                'driver_id' => $request->driver_id,
                'request_type' => 'Choose',
                'status' => 'Pending',
                'lat' => $request->lat,
                'lng' => $request->lng,
            ]);
    
            // ✅ Notification for driver
            Notification::create([
                'user_id' => Auth::id(),
                'driver_id' => $request->driver_id,
                'booking_id' => $booking->id,
                'title' => 'New Booking Request',
                'message' => (Auth::user()->name ?? Auth::user()->first_name.' '.Auth::user()->last_name)
                             . ' has requested a booking from you.',
            ]);
    
            DB::commit();
    
            // ✅ Socket emit (only for this driver)
            Http::withoutVerifying()->post(env('SOCKET_SERVER_URL').'/emit/new-choose-booking', [
                'booking' => $booking,
                'driver_id' => $request->driver_id,
                'user_id'=>Auth::id()
            ]);
    
            return response()->json([
                'status' => 1,
                'message' => 'Booking created successfully. Driver notified.',
                'data' => $booking,
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'message' => 'Failed to create booking.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function instantBooking(Request $request)
    {
        try {
            if (Auth::user()->role !== 'user') {
                return response()->json([
                    'status' => 0,
                    'message' => 'Only users can create bookings.',
                ], 403);
            }
    
            $request->validate([
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
            ]);
    
            $radius = 5; // km
            $pickupLat = $request->lat;
            $pickupLng = $request->lng;
    
            // ✅ Find nearby drivers (5km radius)
            $drivers = User::select(
                    'id',
                    'current_lat',
                    'current_lng',
                    DB::raw("CAST(ROUND(6371 * acos(
                        cos(radians($pickupLat)) 
                        * cos(radians(current_lat)) 
                        * cos(radians(current_lng) - radians($pickupLng)) 
                        + sin(radians($pickupLat)) 
                        * sin(radians(current_lat))
                    ), 2) AS DECIMAL(5,2)) AS distance")
                )
                ->where('role', 'driver')
                ->where('is_active', 1)
                ->having('distance', '<=', $radius)
                ->orderBy('distance', 'asc')
                ->get();
            // return $drivers;
            if ($drivers->isEmpty()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'No drivers available nearby. Please try again later.',
                ], 404);
            }
    
            // ✅ Transaction start
            DB::beginTransaction();
    
            $booking = BookingRequest::create([
                'user_id' => Auth::user()->id,
                'request_type' => 'Request',
                'status' => 'Pending',
                'lat' => $request->lat,
                'lng' => $request->lng,
            ]);
    
            foreach ($drivers as $driver) {
                Notification::create([
                    'user_id' => Auth::id(),
                    'driver_id' => $driver->id,
                    'booking_id' => $booking->id,
                    'title' => 'Instant Booking Request',
                    'message' => (Auth::user()->name ?? Auth::user()->first_name.' '.Auth::user()->last_name) 
                                 . ' mada a request!',
                ]);
            }
    
            DB::commit();
    
            $bookingArray = $booking->toArray();
            $bookingArray['name'] = Auth::user()->name ?? Auth::user()->first_name.' '.Auth::user()->last_name;
            $bookingArray['avatar'] = Auth::user()->avatar ?? null;
    
            // ✅ Socket emit to all nearby drivers
            Http::withoutVerifying()->post(env('SOCKET_SERVER_URL').'/emit/new-instant-booking', [
                'booking' => $bookingArray,
                'driver_ids' => $drivers,
                'status'=>'Pending'
            ]);
    
            return response()->json([
                'status' => 1,
                'message' => 'Instant booking created. Nearby drivers notified.',
                'data' => [
                    'booking' => $bookingArray,
                    'total_drivers' => $drivers->count()
                ],
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'message' => 'Failed to create instant booking.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    private function sendPushNotification($token, $data)
    {
        $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
        $serverKey = env('FCM_SERVER_KEY');
    
        $payload = [
            'to' => $token,
            'notification' => [
                'title' => $data['title'] ?? '',
                'body'  => $data['body'] ?? '',
                'sound' => 'default',
            ],
            'data' => $data,
        ];
    
        try {
            $response = Http::withHeaders([
                'Authorization' => "key=$serverKey",
                'Content-Type'  => 'application/json',
            ])->post($fcmUrl, $payload);
    
            if ($response->failed()) {
                \Log::error('❌ FCM Push Failed', [
                    'token'   => $token,
                    'data'    => $data,
                    'error'   => $response->body(),
                    'status'  => $response->status(),
                ]);
            } else {
                \Log::info('✅ FCM Push Sent', [
                    'token' => $token,
                    'data'  => $data,
                    'resp'  => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('⚠️ FCM Exception', [
                'message' => $e->getMessage(),
                'token'   => $token,
                'data'    => $data,
            ]);
        }
    }


    public function getScheduledBookings()
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
    
        try {
            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');
    
            $bookings = DB::transaction(function () use ($user, $today) {
                if ($user->role == 'driver') {
                    return Booking::where('driver_id', $user->id)
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
                                'passenger' => [
                                    'id' => $booking->user->id,
                                    'name' => $booking->driver->name ??
                                        trim(($booking->driver->first_name ?? '') . ' ' . ($booking->driver->last_name ?? ''))
                                        ?: $booking->driver->business_name,
                                    'avatar' => $booking->user->avatar ?? null,
                                ]
                            ];
                        });
                } else {
                    return Booking::where('user_id', $user->id)
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
                                'amount' => $booking->amount ?? null,
                                'driver' => [
                                    'id' => $booking->driver->id,
                                    'name' => $booking->driver->name ??
                                        trim(($booking->driver->first_name ?? '') . ' ' . ($booking->driver->last_name ?? ''))
                                        ?: $booking->driver->business_name,
                                    'avatar' => $booking->driver->avatar ?? null,
                                    'reviews_count' => $booking->driver->reviews()->count(),
                                    'avg_rating' => round($booking->driver->reviews()->avg('rating'), 1),
                                ]
                            ];
                        });
                }
            });
    
            return response()->json([
                'status' => 1,
                'message' => 'Scheduled bookings retrieved successfully.',
                'data' => $bookings
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error fetching scheduled bookings', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
    
            return response()->json([
                'status' => 0,
                'message' => 'Failed to fetch scheduled bookings. Please try again later.',
            ], 500);
        }
    }


    public function getBookingHistory()
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
    
        try {
            $user = Auth::user();
            $today = Carbon::now()->format('Y-m-d');
    
            $bookings = DB::transaction(function () use ($user, $today) {
                if ($user->role == 'driver') {
                    return Booking::where('driver_id', $user->id)
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
                                'passenger' => [
                                    'id' => $booking->user->id,
                                    'name' => $booking->driver->name ??
                                        trim(($booking->driver->first_name ?? '') . ' ' . ($booking->driver->last_name ?? ''))
                                        ?: $booking->driver->business_name,
                                    'avatar' => $booking->user->avatar ?? null,
                                ]
                            ];
                        });
                } else {
                    return Booking::where('user_id', $user->id)
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
                                'driver' => [
                                    'id' => $booking->driver->id,
                                    'name' => $booking->driver->name ??
                                        trim(($booking->driver->first_name ?? '') . ' ' . ($booking->driver->last_name ?? ''))
                                        ?: $booking->driver->business_name,
                                    'avatar' => $booking->driver->avatar ?? null,
                                    'reviews_count' => $booking->driver->reviews()->count(),
                                    'avg_rating' => round($booking->driver->reviews()->avg('rating'), 1),
                                ]
                            ];
                        });
                }
            });
    
            return response()->json([
                'status' => 1,
                'message' => 'Booking history retrieved successfully.',
                'data' => $bookings
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error fetching booking history', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
    
            return response()->json([
                'status' => 0,
                'message' => 'Failed to fetch booking history. Please try again later.',
            ], 500);
        }
    }

    
    public function getBookingDetail($id)
    {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
    
        try {
            $user = Auth::user();
    
            $booking = DB::transaction(function () use ($id) {
                return Booking::with(['user', 'driver'])
                    ->where('id', $id)
                    ->first();
            });
    
            if (!$booking) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Booking not found.'
                ], 404);
            }
    
            // ✅ Agar driver hai
            if ($user->role == 'driver') {
                $data = [
                    'id' => $booking->id,
                    'passenger_name' => $booking->passenger_name,
                    'location' => $booking->location,
                    'ride_date' => $booking->ride_date,
                    'ride_time' => $booking->ride_time,
                    'distance' => $booking->distance,
                    'status' => $booking->status,
                    'request_type' => $booking->request_type,
                    'amount' => $booking->amount ?? null,
                    'passenger' => [
                        'id' => $booking->user->id,
                        'name' => $booking->user->name,
                        'avatar' => $booking->user->avatar ?? null,
                    ]
                ];
            } else {
                // ✅ Agar passenger hai
                $data = [
                    'id' => $booking->id,
                    'location' => $booking->location,
                    'ride_date' => $booking->ride_date,
                    'ride_time' => $booking->ride_time,
                    'distance' => $booking->distance,
                    'status' => $booking->status,
                    'request_type' => $booking->request_type,
                    'amount' => $booking->amount ?? null,
                    'driver' => [
                        'id' => $booking->driver->id,
                        'name' => $booking->driver->name,
                        'avatar' => $booking->driver->avatar ?? null,
                        'reviews_count' => $booking->driver->reviews()->count(),
                        'avg_rating' => round($booking->driver->reviews()->avg('rating'), 1),
                    ]
                ];
            }
    
            return response()->json([
                'status' => 1,
                'message' => 'Booking detail retrieved successfully.',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error fetching booking detail', [
                'error' => $e->getMessage(),
                'booking_id' => $id,
                'user_id' => Auth::id(),
            ]);
    
            return response()->json([
                'status' => 0,
                'message' => 'Failed to fetch booking detail. Please try again later.',
            ], 500);
        }
    }

    public function cancelBooking(Request $request, $id)
    {
        // return $request->cancel_reason;
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        // return auth()->user()->id;
        try {
            $booking = DB::transaction(function () use ($id) {
                return BookingRequest::where('id', $id)
                    ->lockForUpdate()
                    ->first();
            });
            // return $booking;
            if (!$booking) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Booking not found.',
                ], 404);
            }
            if (Auth::user()->role === 'driver') {
                if ($booking->status !== 'Accepted') {
                    return response()->json([
                        'status' => 0,
                        'message' => 'Driver can only cancel accepted bookings.',
                    ], 400);
                }
            } else {
                if (!in_array($booking->status, ['Pending', 'Accepted'])) {
                    return response()->json([
                        'status' => 0,
                        'message' => 'User can only cancel pending or accepted bookings.',
                    ], 400);
                }
            }
            // return $request->cancel_reason;
            DB::transaction(function () use ($booking, $request) {
                $booking->status = 'Cancelled';
                $booking->cancelled_by = Auth::id();
                $booking->cancelled_by_role = Auth::user()->role;
                $booking->cancel_reason = $request->cancel_reason ?? 'No reason provided';
                $booking->save();
            });
            // return $request->cancel_reason;
            if ($booking->status === 'Cancelled') {
                Http::withoutVerifying()->post(env('SOCKET_SERVER_URL').'/emit/booking-cancelled', [
                    'booking_id' => $booking->id,
                    'cancelled_by' => Auth::id(),
                    'cancelled_by_role' => Auth::user()->role,
                    'cancel_reason' => $request->cancel_reason,
                ]);                
                Http::withoutVerifying()->post(env('SOCKET_SERVER_URL').'/emit/booking-closed', [
                    'booking_id' => $booking->id,
                    'driver_id' => $booking->driver_id,
                    'status'=>'Cancelled'
                ]);
            }    
            return response()->json([
                'status' => 1,
                'message' => 'Booking cancelled successfully.',
                'data' => $booking,
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error cancelling booking', [
                'error' => $e->getMessage(),
                'booking_id' => $id,
                'user_id' => Auth::id(),
            ]);
    
            return response()->json([
                'status' => 0,
                'message' => 'Failed to cancel booking. Please try again later.',
            ], 500);
        }
    }

    public function driverResponse(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:booking_requests,id',
            'driver_id'  => 'required|exists:users,id',
            'status'     => 'required|in:Accepted,Rejected',
        ]);
    
        if (Auth::user()->role !== 'driver') {
            return response()->json([
                'status' => 0,
                'message' => 'Only drivers can accept or reject bookings.',
            ], 403);
        }
    
        try {
            $booking = DB::transaction(function () use ($request) {
                // Lock row so no race condition occurs
                $booking = BookingRequest::where('id', $request->booking_id)
                    ->lockForUpdate()
                    ->firstOrFail();
    
                $driver = User::findOrFail($request->driver_id);
    
                // RULE 1: Sirf Pending state me accept/reject allowed hai
                if ($booking->status === 'Pending') {
                    if ($request->status === 'Accepted') {
                        $booking->status = 'Accepted';
                        $booking->driver_id = $request->driver_id;
                    } elseif ($request->status === 'Rejected') {
                        $booking->status = 'Rejected';
                        $booking->driver_id = $request->driver_id;
                    }
                }
                // RULE 2: Agar pehle se accepted hai
                elseif ($booking->status === 'Accepted') {
                    if ($request->status === 'Accepted') {
                        throw new \Exception('This booking has already been accepted by another driver.');
                    }
                    if ($request->status === 'Rejected') {
                        throw new \Exception('This booking is already accepted. Cannot reject now.');
                    }
                }
                // RULE 3: Agar pehle se rejected hai
                elseif ($booking->status === 'Rejected') {
                    if ($request->status === 'Accepted') {
                        throw new \Exception('This booking has already been rejected. Cannot accept now.');
                    }
                }
                // RULE 4: Agar complete/cancelled already hai
                else {
                    throw new \Exception('This booking is already closed.');
                }
    
                $booking->save();
    
                // Socket emit -> driver response
                Http::withoutVerifying()->post(env('SOCKET_SERVER_URL').'/emit/driver-response', [
                    'booking' => $booking,
                    'driver'  => $driver,
                    'status'  => $request->status,
                ]);
    
                // Agar booking close karni ho
                if ($booking->status === 'Accepted') {
                    Http::withoutVerifying()->post(env('SOCKET_SERVER_URL').'/emit/booking-closed', [
                        'booking_id' => $booking->id,
                        'driver_id' => $request->driver_id,
                        'status'=>'Accepted'
                    ]);
                }
    
                return $booking;
            });
    
            return response()->json([
                'status' => 1,
                'message' => "Booking {$request->status} successfully.",
                'data' => $booking
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Driver response failed', [
                'error' => $e->getMessage(),
                'booking_id' => $request->booking_id,
                'driver_id' => $request->driver_id,
            ]);
    
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage() ?: 'Something went wrong.',
            ], 400);
        }
    }

}
