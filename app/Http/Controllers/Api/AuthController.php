<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Content;
use App\Models\Booking;
use App\Models\Review;
use App\Traits\ApiResponser;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
class AuthController extends Controller
{
    use ApiResponser;
    private function handleFileUpload($request, $fieldName, $folder, $existingFilePath = null) {
        if ($request->hasFile($fieldName)) {
            if ($existingFilePath) {
                Storage::disk('public')->delete($existingFilePath);
            }
            return $request->file($fieldName)->store($folder, 'public');
        }
        return $existingFilePath;
    }
    public function signup(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:user,driver',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400);
        }
    
        // Create user
        $user = User::create([
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
        ]);
    
        $otp = '123456';
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();
    
        /**
         * ðŸ”¹ Dummy Bookings
         */
        if ($user->role === 'user') {
            // User signup hua -> driver randomly pick karna hai
            $driver = User::where('role', 'driver')->inRandomOrder()->first();
        
            if ($driver) {
                // History bookings (past dates)
                for ($i = 1; $i <= 2; $i++) {
                    Booking::create([
                        'user_id'        => $user->id,
                        'driver_id'      => $driver->id,
                        'passenger_name' => "Passenger-{$user->id}-{$i}",
                        'location'       => "Old Ride Location {$i}",
                        'request_type'   => 'Choose',
                        'status'         => 'Completed',
                        'ride_date'      => now()->subDays($i)->toDateString(),
                        'ride_time'      => now()->subDays($i)->format('H:i:s'),
                        'distance'       => rand(5, 25),
                        'amount'         => rand(200, 1000),
                    ]);
                }
        
                // Upcoming booking (future date)
                Booking::create([
                    'user_id'        => $user->id,
                    'driver_id'      => $driver->id,
                    'passenger_name' => "Passenger-{$user->id}-future",
                    'location'       => 'Future Ride Location',
                    'request_type'   => 'Schedule',
                    'status'         => 'Pending',
                    'ride_date'      => now()->addDays(2)->toDateString(),
                    'ride_time'      => now()->addHours(3)->format('H:i:s'),
                    'distance'       => rand(10, 50),
                    'amount'         => rand(200, 1000),
                ]);
            }
        } elseif ($user->role === 'driver') {
            // Driver signup hua -> user randomly pick karna hai
            $customer = User::where('role', 'user')->inRandomOrder()->first();
        
            if ($customer) {
                // History bookings (past dates)
                for ($i = 1; $i <= 2; $i++) {
                    Booking::create([
                        'user_id'        => $customer->id,
                        'driver_id'      => $user->id,
                        'passenger_name' => "Passenger-{$customer->id}-{$i}",
                        'location'       => "Old Ride Location {$i}",
                        'request_type'   => 'Choose',
                        'status'         => 'Completed',
                        'ride_date'      => now()->subDays($i)->toDateString(),
                        'ride_time'      => now()->subDays($i)->format('H:i:s'),
                        'distance'       => rand(5, 25),
                        'amount'         => rand(200, 1000),
                    ]);
                }
        
                // Upcoming booking (future date)
                Booking::create([
                    'user_id'        => $customer->id,
                    'driver_id'      => $user->id,
                    'passenger_name' => "Passenger-{$customer->id}-future",
                    'location'       => 'Future Ride Location',
                    'request_type'   => 'Schedule',
                    'status'         => 'Pending',
                    'ride_date'      => now()->addDays(2)->toDateString(),
                    'ride_time'      => now()->addHours(3)->format('H:i:s'),
                    'distance'       => rand(10, 50),
                    'amount'         => rand(200, 1000),
                ]);
            }
        }


        if ($user->role === 'driver') {
            // Do dummy users leke unse review dalwa dete hain
            $reviewers = User::where('role', 'user')->take(2)->get();
        
            // Agar dummy reviewers exist karte hain tabhi insert karenge
            foreach ($reviewers as $rev) {
                // check duplicate na ho
                $exists = Review::where('user_id', $rev->id)
                                ->where('driver_id', $user->id)
                                ->exists();
                if (!$exists) {
                    Review::create([
                        'user_id'  => $rev->id,
                        'driver_id'=> $user->id,
                        'rating'   => rand(3, 5), // random rating between 3-5
                        'review'   => 'This is a dummy review from user ' . $rev->id,
                    ]);
                }
            }
        }        
    
        return response()->json([
            'success' => 1,
            'message' => 'Signup successful',
            'data' => $user
        ]);
    }

    public function login(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            $error = $validator->errors()->first();

            return response()->json([
                'success' => 0,
                'message' => $error,
            ], 400);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user || !password_verify($request->password, $user->password)) {
            return response()->json([
                'success' => 0,
                'message' => 'Invalid credentials',
            ], 400);
        }
        if ($user->role !== $request->role) {
            return response()->json([
                'success' => 0,
                'message' => 'Invalid login role ',
            ], 400);
        }
        if ($user->is_verified == 0) {
            return response()->json([
                'success' => 0,
                'message' => 'Your account is not verified yet.',
            ], 403);
        }
    
        // if ($user->profile_completed == 0) {
        //     return response()->json([
        //         'success' => 0,
        //         'message' => 'Please complete your profile before logging in.',
        //     ], 403);
        // }
        $token = $user->createToken('curbcream')->plainTextToken;
        if ($user->role === 'driver') {
            $user->loadCount('reviews');
            $user->loadAvg('reviews', 'rating');
    
            $user->reviews_avg_rating = $user->reviews_avg_rating 
                ? round($user->reviews_avg_rating, 1) 
                : 0;
        }
        
        return response()->json([
            'success' => 1,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ]
        ]);
    }
    public function sendOtp(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        $user = User::where('email', $request->email)->first();
        // $otp = rand(100000, 999999);
        $otp = '123456'; 
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();
        // Mail::to($user->email)->send(new \App\Mail\SendOtpMail($otp));
        return response()->json([
            'success' => 1,
            'message' => 'OTP sent successfully',
        ]);
    }
    public function verifyOtp(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user || $user->otp !== $request->otp || now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'success' => 0,
                'message' => 'Invalid or expired OTP',
            ], 400);
        }
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->is_verified = true;
        $user->save();
        $token = $user->createToken('curbcream')->plainTextToken;
        return response()->json([
            'success' => 1,
            'message' => 'OTP verified successfully',
            'data' => [
                'user' => $user,
                'bearer_token' => $token,
            ]
        ]);
    }
    public function resendOtp(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        $user = User::where('email', $request->email)->first();
        // $otp = rand(100000, 999999);
        $otp = '123456'; 
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();
        // Mail::to($user->email)->send(new \App\Mail\SendOtpMail($otp));
        return response()->json([
            'success' => 1,
            'message' => 'OTP resent successfully',
        ]);
    }
    public function forgotPassword(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        $user = User::where('email', $request->email)->first();
        // $resetToken = Str::random(6);
        $otp = '123456';
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(30);
        $user->save();
        // Mail::to($user->email)->send(new \App\Mail\SendOtpMail($otp));
        return response()->json([
            'success' => 1,
            'message' => 'Otp sent to your email',
            'data' => [
                'otp' => $otp,
            ]
        ]);
    }
    public function resetPassword(Request $request) {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        
        $user = User::where('email', $request->email)->first();
        if (!$user || $user->otp !== $request->otp || ($user->otp_expires_at && now()->greaterThan($user->otp_expires_at))) {
            return response()->json([
                'success' => 0,
                'message' => 'Invalid or expired otp',
            ], 400);
        }
        
        $user->password = bcrypt($request->password);
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();
    
        return response()->json([
            'success' => 1,
            'message' => 'Password reset successfully',
        ]);
    }

    public function updateProfile(Request $request) {
        $authId = AuthFacade::user()->id;
        $user = User::find($authId); // Simplified to use find method
        
        if (!$user) {
            return response()->json([
                'success' => 0,
                'message' => 'User not authenticated',
            ], 401);
        }
    
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable',
            'address' => 'nullable|string',
            'bio' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'open_time' => 'nullable|string',
            'close_time' => 'nullable|string',
            'vehicle_category' => 'nullable|string',
            'driver_license' => 'nullable|image|mimes:pdf,jpg,jpeg,png',
            'taxi_operator_license' => 'nullable|image|mimes:pdf,jpg,jpeg,png',
            'vehicle_registration' => 'nullable|image|mimes:pdf,jpg,jpeg,png',
            'insurance_card' => 'nullable|image|mimes:pdf,jpg,jpeg,png',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400);
        }
    
        $profileData = [
            'email' => $request->email ?? $user->email,
            'phone' => $request->phone ?? $user->phone,
            'address' => $request->address ?? $user->address,
            'bio' => $request->bio ?? $user->bio,
        ];
    
        if ($user->role == 'user') {
            $profileData['first_name'] = $request->first_name ?? $user->first_name;
            $profileData['last_name'] = $request->last_name ?? $user->last_name;
            $profileData['name'] = $request->first_name.' '.$request->last_name;
            
            $profileData['avatar'] = $this->handleFileUpload($request, 'avatar', 'user/avatars', $user->avatar);
        } else {
            $profileData['name'] = $request->first_name.' '.$request->last_name;
            $profileData['business_name'] = $request->business_name ?? $user->business_name;
            $profileData['location'] = $request->location ?? $user->location;
            $profileData['open_time'] = $request->open_time ?? $user->open_time;
            $profileData['close_time'] = $request->close_time ?? $user->close_time;
            $profileData['vehicle_category'] = $request->vehicle_category ?? $user->vehicle_category;
    
            $profileData['avatar'] = $this->handleFileUpload($request, 'avatar', 'driver/avatars', $user->avatar);
            $profileData['profile_picture'] = $this->handleFileUpload($request, 'profile_picture', 'driver/profile_pic', $user->profile_picture);
            $profileData['driver_license'] = $this->handleFileUpload($request, 'driver_license', 'driver/documents', $user->driver_license);
            $profileData['taxi_operator_license'] = $this->handleFileUpload($request, 'taxi_operator_license', 'driver/documents', $user->taxi_operator_license);
            $profileData['vehicle_registration'] = $this->handleFileUpload($request, 'vehicle_registration', 'driver/documents', $user->vehicle_registration);
            $profileData['insurance_card'] = $this->handleFileUpload($request, 'insurance_card', 'driver/documents', $user->insurance_card);
        }
    
        $isFirstTime = $user->profile_completed ? false : true;
    
        $user->update($profileData);
    
        $user->profile_completed = true;
        $user->save();
    
        $message = $isFirstTime ? 'Profile Created Successfully' : 'Profile updated successfully';
    
        return response()->json([
            'success' => 1,
            'message' => $message,
            'data' => [
                'user' => $user,
            ]
        ]);
    }

    public function changePassword(Request $request) {
        $id = AuthFacade::user()->id;
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => 0,
                'message' => 'User not authenticated',
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|min:6',
            'new_password' => 'required|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        if (!password_verify($request->current_password, $user->password)) {
            return response()->json([
                'success' => 0,
                'message' => 'Current password is incorrect',
            ], 400);
        }
        $user->password = bcrypt($request->new_password);
        $user->save();
        return response()->json([
            'success' => 1,
            'message' => 'Password changed successfully',
        ]);
    }
    public function logout(Request $request) {
        $user = AuthFacade::user();
        if(!$user) {
            return response()->json([
                'success' => 0,
                'message' => 'User not authenticated',
            ], 401);
        }
        $user->tokens()->delete();
        return response()->json([
            'success' => 1,
            'message' => 'Logged out successfully',
        ]);
    }
    public function allUsers(Request $request) {
        $users = User::all();
        return response()->json([
            'success' => 1,
            'message' => 'Users retrieved successfully',
            'data' => $users,
        ]);
    }
    public function getUser($id) {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => 0,
                'message' => 'User not found',
            ], 404);
        }
        return response()->json([
            'success' => 1,
            'message' => 'User retrieved successfully',
            'data' => $user,
        ]);
    } 
    public function getDrivers(Request $request)
    {
        // return $request;
        $userId = $request->user_id ?? auth()->id();
    
        // ✅ User ki current location lat/lng required
        $userLat = $request->lat;
        $userLng = $request->lng;
    
        if (!$userLat || !$userLng) {
            return response()->json([
                'success' => 0,
                'message' => 'User location (lat, lng) is required',
            ], 422);
        }
    
        $radius = 10; // km
        $drivers = User::where(['role' => 'driver', 'profile_completed' => 1,'is_active'=>1])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->with(['favouritedBy' => function($q) use ($userId) {
                $q->where('user_id', $userId);
            }])
            ->get();
        if ($drivers->isEmpty()) {
            return response()->json([
                'success' => 0,
                'message' => 'No drivers found within 10 km radius',
            ], 404);
        }
    
        $drivers->transform(function ($driver) {
            // Force clean 2 decimals
            $driver->distance = number_format((float)$driver->distance, 2, '.', '');
            $driver->distance_miles = number_format((float)$driver->distance * 0.621371, 2, '.', '');
        
            $driver->reviews_avg_rating = $driver->reviews_avg_rating
                ? round((float)$driver->reviews_avg_rating, 1)
                : 0;
        
            $driver->is_favourite = $driver->favouritedBy->isNotEmpty();
        
            unset($driver->favouritedBy);
        
            return $driver;
        });
    
        return response()->json([
            'success' => 1,
            'message' => 'Drivers retrieved successfully',
            'data' => $drivers,
        ]);
    }
    public function getDriverDetail($id)
    {
        $driver = User::where('role', 'driver')
            ->where('id', $id)
            ->withCount('reviews')                // total reviews count
            ->withAvg('reviews', 'rating')        // avg rating
            ->with(['products'])  // driver ke products aur reviews ke sath reviewer user
            ->first();

        if (!$driver) {
            return response()->json([
                'success' => 0,
                'message' => 'Driver not found',
            ], 404);
        }

        // round avg rating
        $driver->reviews_avg_rating = $driver->reviews_avg_rating
            ? round($driver->reviews_avg_rating, 1)
            : 0;
        $driver->is_favourite = $driver->favouritedBy->isNotEmpty();
    
        unset($driver->favouritedBy);
        return response()->json([
            'success' => 1,
            'message' => 'Driver details retrieved successfully',
            'data' => $driver,
        ]);
    }
    public function searchDrivers(Request $request)
    {
        try {
            $userId = $request->user_id ?? auth()->id();
            
            $request->validate([
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
            ]);
            
            $userLat = $request->lat;
            $userLng = $request->lng;
            $maxDistance = $request->distance ?? 10; // km (default 10km)
            $minRating = $request->rating ?? 0;      // minimum rating (default 0)
    
            $query = User::where([
                    'role' => 'driver', 
                    'profile_completed' => true,
                    'is_active' => 1
                ])
                ->withCount('reviews')
                ->withAvg('reviews', 'rating')
                ->with(['favouritedBy' => function($q) use ($userId) {
                    $q->where('user_id', $userId);
                }]);
            // $query = User::where([
            //         'role' => 'driver', 
            //         'profile_completed' => true,
            //         'is_active' => 1
            //     ])
            //     ->select(
            //         'users.*',
            //         DB::raw("ROUND(6371 * acos(
            //             cos(radians($userLat)) 
            //             * cos(radians(current_lat)) 
            //             * cos(radians(current_lng) - radians($userLng)) 
            //             + sin(radians($userLat)) 
            //             * sin(radians(current_lat))
            //         ), 2) AS distance")
            //     )
            //     ->withCount('reviews')
            //     ->withAvg('reviews', 'rating')
            //     ->with(['favouritedBy' => function($q) use ($userId) {
            //         $q->where('user_id', $userId);
            //     }])
            //     ->having('distance', '<=', $maxDistance);
    

            if ($minRating > 0) {
                $query->having('reviews_avg_rating', '>=', $minRating);
            }
    
            // $query->orderBy('distance', 'asc');
    
            $drivers = $query->get();
    
            if ($drivers->isEmpty()) {
                return response()->json([
                    'success' => 0,
                    'message' => 'No drivers found matching your criteria',
                ], 404);
            }
    
            // ✅ Transform data
            $drivers->transform(function ($driver) {
                // $driver->distance = number_format((float)$driver->distance, 2, '.', '');
                // $driver->distance_miles = number_format((float)$driver->distance * 0.621371, 2, '.', '');
                
                $driver->reviews_avg_rating = $driver->reviews_avg_rating
                    ? round((float)$driver->reviews_avg_rating, 1)
                    : null;
                
                $driver->is_favourite = $driver->favouritedBy->isNotEmpty();
                unset($driver->favouritedBy);
                
                return $driver;
            });
    
            return response()->json([
                'success' => 1,
                'message' => 'Drivers retrieved successfully',
                'data' => $drivers,
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => 'Error searching drivers: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function addProduct(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'products' => 'required|array|min:1',
                'products.*.name'  => 'required|string|max:255',
                'products.*.price' => ['required','numeric','regex:/^\d{1,6}(\.\d{1,2})?$/'],
                'products.*.image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:5120',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => 0,
                    'message' => $validator->errors()->first(),
                ], 400);
            }
    
            $created = [];
    
            foreach ($request->input('products', []) as $index => $payload) {
                try {
                    $file = $request->file("products.$index.image");
                    $path = $file ? $file->store('products', 'public') : null;
    
                    $product = Product::create([
                        'name'    => $payload['name'],
                        'price'   => $payload['price'],
                        'images'  => $path ? [$path] : [],
                        'user_id' => AuthFacade::id(),
                    ]);
    
                    $created[] = $product;
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => 0,
                        'message' => 'Error uploading product image: ' . $e->getMessage(),
                    ], 500);
                }
            }
    
            return response()->json([
                'success' => 1,
                'message' => 'Products added successfully',
                'data'    => $created,
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function allProducts() {
        $products = Product::all();
        return response()->json([
            'success' => 1,
            'message' => 'Products retrieved successfully',
            'data' => $products,
        ]);
    }
    public function getProduct($id) {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => 0,
                'message' => 'Product not found',
            ], 404);
        }
        return response()->json([
            'success' => 1,
            'message' => 'Product retrieved successfully',
            'data' => $product,
        ]);
    }
    public function updateProduct(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => 0,
                'message' => 'Product not found',
            ], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'name'   => 'sometimes|required|string|max:255',
            'price'  => 'sometimes|required|numeric|regex:/^\d{1,6}(\.\d{1,2})?$/',
            'images'   => 'sometimes|required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:5120',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400);
        }
    
        if ($request->has('name')) {
            $product->name = $request->name;
        }
    
        if ($request->has('price')) {
            $product->price = $request->price;
        }
    
        if ($request->hasFile('images')) {
            if (!empty($product->images)) {
                foreach ((array) $product->images as $oldImage) {
                    $existingImageFullPath = storage_path('app/public/' . $oldImage);
                    if (file_exists($existingImageFullPath)) {
                        @unlink($existingImageFullPath);
                    }
                }
            }
        
            $imagePaths = [];
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store('products', 'public');
            }
        
            $product->images = $imagePaths;
        }

    
        $product->save();
    
        return response()->json([
            'success' => 1,
            'message' => 'Product updated successfully',
            'data'    => $product,
        ]);
    }


    public function getProductsByUser($userId) {
        $products = Product::where('user_id', $userId)->get();
        if ($products->isEmpty()) {
            return response()->json([
                'success' => 0,
                'message' => 'No products found for this user',
            ], 404);
        }
        return response()->json([
            'success' => 1,
            'message' => 'Products retrieved successfully',
            'data' => $products,
        ]);
    }
    public function deleteProduct($id) {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => 0,
                'message' => 'Product not found',
            ], 404);
        }
        if ($product->images) {
            foreach ($product->images as $imagePath) {
                $imageFullPath = storage_path('app/public/' . $imagePath);
                if (file_exists($imageFullPath)) {
                    unlink($imageFullPath); 
                }
            }
        }
        $product->delete();
        return response()->json([
            'success' => 1,
            'message' => 'Product deleted successfully',
        ]);
    }    
    public function page($slug)
    {
        try {
            $page = Content::select('id','type','description')->where('type', $slug)->get();
            if(count($page)>0){
              
                return response()->json([
                    'success' => 1,
                    'message' => 'Data retrieved successfully',
                    'data' => $page,
                ]);
            }
            else{
                return response()->json([
                    'success' => 0,
                    'message' => 'No data found',
                ], 404);
            }
        }  catch (\Exception $e) {
            return $this->respondInternalError('An error occurred: ' . $e->getMessage());
        }
    }

    public function deleteAccount(Request $request)
    {
        try {
            $user = AuthFacade::user();
            $user->products()->delete();
    
            $user->delete();
    
            return response()->json([
                'success' => 1,
                'message' => 'Account deleted successfully',
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function toggleActive(Request $request)
    {
        try {
            $user = AuthFacade::user();
    
            $user->is_active = !$user->is_active;
    
            $user->save();
            return response()->json([
                'success' => 1,
                'message' => 'Account status updated',
                'is_active' => (int) $user->is_active,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function toggleNotification(Request $request)
    {
        try {
            $user = AuthFacade::user();
    
            $user->is_notification = !$user->is_notification;
    
            $user->save();
    
            return response()->json([
                'success' => 1,
                'message' => 'Notification status updated',
                'is_notification' => (int) $user->is_notification,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function updateLocation(Request $request)
    {
        try {
            $user = AuthFacade::user();
    
            $validator = Validator::make($request->all(), [
                'current_lat' => 'required|numeric',
                'current_lng' => 'required|numeric',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'success' => 0,
                    'message' => $validator->errors()->first(),
                ], 400);
            }
    
            $user->current_lat = $request->current_lat;
            $user->current_lng = $request->current_lng;
    
            $user->save();
    
            return response()->json([
                'success' => 1,
                'message' => 'Location updated successfully',
                'data' => [
                    'id'=> $user->id,
                    'latitude' => $user->current_lat,
                    'longitude' => $user->current_lng,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => 0,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}
