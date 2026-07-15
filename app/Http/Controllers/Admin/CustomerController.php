<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $search = $request->get('search');

        $query = User::where('role', 'user');

        if ($filter === 'active') {
            $query->where('is_active', 1)->where('is_banned', false);
        } elseif ($filter === 'inactive') {
            $query->where(function ($q) {
                $q->where('is_active', 0)->orWhere('is_banned', true);
            });
        } elseif ($filter === 'new') {
            $query->where('created_at', '>=', now()->subDays(30));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $customers = $query->withCount('bookings')->latest()->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers', 'filter', 'search'));
    }

    public function show($id)
    {
        $customer = User::where('role', 'user')->findOrFail($id);
        $bookingHistory = Booking::with('driver')->where('user_id', $customer->id)->latest()->get();
        $reviews = Review::where('user_id', $customer->id)->latest()->get();
        $disputes = Dispute::with('driver')->where('user_id', $customer->id)->latest()->get();

        return view('admin.customers.show', compact('customer', 'bookingHistory', 'reviews', 'disputes'));
    }

    public function toggleStatus($id)
    {
        $customer = User::where('role', 'user')->findOrFail($id);
        $customer->is_active = !(bool) $customer->is_active;
        $customer->save();

        return back()->with('success', $customer->is_active ? 'Customer activated.' : 'Customer deactivated.');
    }

    public function ban($id)
    {
        $customer = User::where('role', 'user')->findOrFail($id);
        $customer->is_banned = !(bool) $customer->is_banned;
        if ($customer->is_banned) {
            $customer->is_active = false;
            $customer->tokens()->delete();
        }
        $customer->save();

        return back()->with('success', $customer->is_banned ? 'Customer banned.' : 'Customer unbanned.');
    }

    public function resetPassword($id)
    {
        $customer = User::where('role', 'user')->findOrFail($id);
        $tempPassword = 'Temp@' . Str::upper(Str::random(6));
        $customer->password = Hash::make($tempPassword);
        $customer->save();
        $customer->tokens()->delete();

        return back()->with('success', "Password reset. Temporary password: {$tempPassword}");
    }
}
