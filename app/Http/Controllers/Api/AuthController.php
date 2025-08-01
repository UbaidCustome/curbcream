<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponser;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Auth as AuthFacade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
class AuthController extends Controller
{
    use ApiResponser;

    public function signup(Request $request) {
        // Validate input with custom error handling
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:user,driver',
        ]);

        // Check if the validation failed
        if ($validator->fails()) {
            // Get the first error message
            $error = $validator->errors()->first();

            // Return response with only the first error message
            return $this->errorResponse($error, 400); // Bad Request
        }

        // If validation passes, create the user
        $user = User::create([
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'success' => 1,
            'message' => 'Signup successful',
            'data' => $user
        ]);
    }
    public function login(Request $request) {
        // Validate input with custom error handling
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // Check if the validation failed
        if ($validator->fails()) {
            // Get the first error message
            $error = $validator->errors()->first();

            // Return response with only the first error message
            return response()->json([
                'success' => 0,
                'message' => $error,
            ], 400); // Bad Request
        }
        $user = User::where('email', $request->email)->first();
        if (!$user || !password_verify($request->password, $user->password)) {
            return response()->json([
                'success' => 0,
                'message' => 'Invalid credentials',
            ], 401); // Unauthorized
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
        // Logic to send OTP
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400); // Bad Request
        }
        $user = User::where('email', $request->email)->first();
        // $otp = rand(100000, 999999);
        $otp = '123456'; 
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();
        // return $user;
        // Mail::to($user->email)->send(new \App\Mail\SendOtpMail($otp));
        // return $otp;
        return response()->json([
            'success' => 1,
            'message' => 'OTP sent successfully',
        ]);
    }
    public function verifyOtp(Request $request) {
        // Logic to verify OTP
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400); // Bad Request
        }
        $user = User::where('email', $request->email)->first();
        if (!$user || $user->otp !== $request->otp || now()->greaterThan($user->otp_expires_at)) {
            return response()->json([
                'success' => 0,
                'message' => 'Invalid or expired OTP',
            ], 400); // Bad Request
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
        // Logic to resend OTP
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400); // Bad Request
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
        // Logic to handle forgot password
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400); // Bad Request
        }
        $user = User::where('email', $request->email)->first();
        // $resetToken = Str::random(6);
        $resetToken = '123456';
        $user->password_reset_token = $resetToken;
        $user->password_reset_expires_at = now()->addMinutes(30);
        $user->save();
        // Here you would send the reset token to the user's email
        return response()->json([
            'success' => 1,
            'message' => 'Password reset link sent to your email',
            'data' => [
                'reset_token' => $resetToken,
            ]
        ]);
    }
    public function resetPassword(Request $request) {
        // Logic to reset password
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'reset_token' => 'required|string',
            'password' => 'required|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400); // Bad Request
        }
        $user = User::where('email', $request->email)->first();
        if (!$user || $user->password_reset_token !== $request->reset_token || now()->greaterThan($user->password_reset_expires_at)) {
            return response()->json([
                'success' => 0,
                'message' => 'Invalid or expired reset token',
            ], 400); // Bad Request
        }
        $user->password = bcrypt($request->password);
        $user->password_reset_token = null;
        $user->password_reset_expires_at = null;
        $user->save();

        return response()->json([
            'success' => 1,
            'message' => 'Password reset successfully',
        ]);
    }
    public function updateProfile(Request $request) {
        // Logic to update user profile
        $authId = AuthFacade::user()->id;
        $user = User::where('id', $authId)->first();
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
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'open_time' => 'nullable|string',
            'close_time' => 'nullable|string',
            'vehicle_category' => 'nullable|string',
            'driving_license' => 'nullable|image|mimes:pdf',
            'vehicle_registration' => 'nullable|image|mimes:pdf',
            'insurence_card' => 'nullable|image|mimes:pdf',
            
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        // Update user profile fields
        if($user->role == 'user'){
            $user->first_name = $request->first_name ?? $user->first_name;
            $user->last_name = $request->last_name ?? $user->last_name;
            $user->email = $request->email ?? $user->email;
            $user->phone = $request->phone ?? $user->phone;
            $user->address = $request->address ?? $user->address;
            $user->bio = $request->bio ?? $user->bio;
            if ($request->hasFile('avatar')) {
                $avatarPath = $request->file('avatar')->store('user/avatars', 'public');
                $user->avatar = $avatarPath; // Assuming you have an 'avatar' column
            }
            $user->save();        
            return response()->json([
                'success' => 1,
                'message' => 'Profile updated successfully',
                'data' => $user,
            ]);
        }
        else{
            $user->business_name = $request->business_name ?? $user->business_name;
            $user->email = $request->email ?? $user->email;
            $user->phone = $request->phone ?? $user->phone;
            $user->location = $request->location ?? $user->location;
            $user->open_time = $request->open_time ?? $user->open_time;
            $user->close_time = $request->close_time ?? $user->close_time;
            $user->vehicle_category = $request->vehicle_category ?? $user->vehicle_category;
            if ($request->hasFile('profile_picture')) {
                $avatarPath = $request->file('profile_picture')->store('driver/profile_pic', 'public');
                $user->profile_picture = $avatarPath; // Assuming you have an 'avatar' column
            }            
            if ($request->hasFile('driving_license')) {
                $avatarPath = $request->file('driving_license')->store('driver/documents', 'public');
                $user->driving_license = $avatarPath; // Assuming you have an 'avatar' column
            }            
            if ($request->hasFile('vehicle_registration')) {
                $avatarPath = $request->file('vehicle_registration')->store('driver/documents', 'public');
                $user->vehicle_registration = $avatarPath; // Assuming you have an 'avatar' column
            }            
            if ($request->hasFile('insurence_card')) {
                $avatarPath = $request->file('insurence_card')->store('driver/documents', 'public');
                $user->insurence_card = $avatarPath; // Assuming you have an 'avatar' column
            }            
        }
    }
    public function changePassword(Request $request) {
        // Logic to change user password
        $id = AuthFacade::user()->id;
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => 0,
                'message' => 'User not authenticated',
            ], 401); // Unauthorized
        }
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|min:6',
            'new_password' => 'required|min:6|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400); // Bad Request
        }
        if (!password_verify($request->current_password, $user->password)) {
            return response()->json([
                'success' => 0,
                'message' => 'Current password is incorrect',
            ], 400); // Bad Request
        }
        $user->password = bcrypt($request->new_password);
        $user->save();
        return response()->json([
            'success' => 1,
            'message' => 'Password changed successfully',
        ]);
    }
    public function logout(Request $request) {
        $user = $request->user();
        if(!$user) {
            return response()->json([
                'success' => 0,
                'message' => 'User not authenticated',
            ], 401); // Unauthorized
        }
        $user->tokens()->delete(); // Revoke all tokens for the user
        return response()->json([
            'success' => 1,
            'message' => 'Logged out successfully',
        ]);
    }
    public function allUsers(Request $request) {
        // Return all users
        $users = User::all();
        return response()->json([
            'success' => 1,
            'message' => 'Users retrieved successfully',
            'data' => $users,
        ]);
    }
    public function getUser($id) {
        // Return user by ID
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'success' => 0,
                'message' => 'User not found',
            ], 404); // Not Found
        }
        return response()->json([
            'success' => 1,
            'message' => 'User retrieved successfully',
            'data' => $user,
        ]);
    } 
}
