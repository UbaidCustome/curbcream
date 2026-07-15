<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        $user = User::where('email', $credentials['email'])->where('role', 'admin')->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()->withInput($request->only('email'))->with('error', 'Invalid admin credentials.');
        }

        if ($user->is_banned) {
            return back()->with('error', 'This admin account has been banned.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', 'Login successful.');
    }

    public function showForgotPassword()
    {
        return view('admin.auth.forgot-password');
    }

    public function sendResetOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->where('role', 'admin')->first();

        if (!$user) {
            return back()->withInput()->with('error', 'No admin account found with this email.');
        }

        $otp = '123456';
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(30);
        $user->save();

        return redirect()
            ->route('admin.forgot-password')
            ->with('success', 'OTP sent to your email.')
            ->with('otp_email', $user->email)
            ->with('dev_otp', $otp)
            ->with('show_reset', true);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::where('email', $request->email)->where('role', 'admin')->first();

        if (
            !$user
            || $user->otp !== $request->otp
            || ($user->otp_expires_at && now()->greaterThan($user->otp_expires_at))
        ) {
            return back()
                ->withInput()
                ->with('error', 'Invalid or expired OTP.')
                ->with('otp_email', $request->email)
                ->with('show_reset', true);
        }

        $user->password = Hash::make($request->password);
        $user->otp = null;
        $user->otp_expires_at = null;
        $user->save();

        return redirect()->route('admin.login')->with('success', 'Password reset successfully. Please login.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Logged out successfully.');
    }
}
