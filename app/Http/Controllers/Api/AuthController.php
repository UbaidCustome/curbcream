<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Product;
use App\Models\Content;
use App\Traits\ApiResponser;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
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
            $error = $validator->errors()->first();

            return response()->json([
                'success' => 0,
                'message' => $error,
            ], 400);
        }

        $user = User::create([
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
        ]);
        
        $otp = '123456'; 
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();
        // Mail::to($user->email)->send(new \App\Mail\SendOtpMail($otp));
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
        $token = $user->createToken('curbcream')->plainTextToken;
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
    public function getDrivers()
    {
        $drivers = User::where(['role' => 'driver', 'profile_completed' => true])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->get();

        if ($drivers->isEmpty()) {
            return response()->json([
                'success' => 0,
                'message' => 'Data not found',
            ], 404);
        }

        // Round average rating to 1 decimal place
        $drivers->transform(function ($driver) {
            $driver->reviews_avg_rating = $driver->reviews_avg_rating 
                ? round($driver->reviews_avg_rating, 1) 
                : null;
            return $driver;
        });

        return response()->json([
            'success' => 1,
            'message' => 'Drivers retrieved successfully',
            'data' => $drivers,
        ]);
    }


    public function addProduct(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'products' => 'required|array|min:1',
                'products.*.name'  => 'required|string|max:255',
                'products.*.price' => ['required','numeric','regex:/^\d{1,6}(\.\d{1,2})?$/'],
                'products.*.image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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
    public function updateProduct(Request $request, $id) {
        $product = Product::find($id);
        if (!$product) {
            return response()->json([
                'success' => 0,
                'message' => 'Product not found',
            ], 404);
        }
    
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|regex:/^\d{1,6}(\.\d{1,2})?$/',
            'image' => 'sometimes|required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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
    
        if ($request->hasFile('image')) {
            if ($product->image) {
                $existingImageFullPath = storage_path('app/public/' . $product->image);
                if (file_exists($existingImageFullPath)) {
                    unlink($existingImageFullPath);
                }
            }
    
            $imagePath = $request->file('image')->store('products', 'public');
            $product->image = $imagePath;
        }
    
        $product->save();
        return response()->json([
            'success' => 1,
            'message' => 'Product updated successfully',
            'data' => $product,
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
   
}
