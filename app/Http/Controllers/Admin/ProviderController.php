<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Product;
use App\Models\Review;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $search = $request->get('search');

        $query = User::where('role', 'driver');

        if ($filter === 'active') {
            $query->where('is_active', 1)->where('is_banned', false)->where('document_status', 'approved');
        } elseif ($filter === 'inactive') {
            $query->where(function ($q) {
                $q->where('is_active', 0)->orWhere('is_banned', true);
            });
        } elseif ($filter === 'pending') {
            $query->whereIn('document_status', ['pending', 'more_info']);
        } elseif ($filter === 'approved') {
            $query->where('document_status', 'approved');
        } elseif ($filter === 'rejected') {
            $query->where('document_status', 'rejected');
        } elseif ($filter === 'subscription_active') {
            $query->where('subscription_status', 'active');
        } elseif ($filter === 'subscription_expired') {
            $query->where('subscription_status', 'expired');
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

        $providers = $query
            ->withCount(['driverBookings as completed_jobs_count' => fn ($q) => $q->where('status', 'Completed')])
            ->withAvg('reviews', 'rating')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $providers->getCollection()->transform(function ($provider) {
            $provider->reviews_avg_rating = $provider->reviews_avg_rating
                ? round((float) $provider->reviews_avg_rating, 1)
                : 0;
            return $provider;
        });

        return view('admin.providers.index', compact('providers', 'filter', 'search'));
    }

    public function show($id)
    {
        $provider = User::where('role', 'driver')
            ->withCount(['driverBookings as completed_jobs_count' => fn ($q) => $q->where('status', 'Completed')])
            ->withAvg('reviews', 'rating')
            ->findOrFail($id);

        $provider->reviews_avg_rating = $provider->reviews_avg_rating
            ? round((float) $provider->reviews_avg_rating, 1)
            : 0;

        $services = Product::where('user_id', $provider->id)->latest()->get();
        $reviews = Review::with('user')->where('driver_id', $provider->id)->latest()->get();
        $jobs = Booking::with('user')->where('driver_id', $provider->id)->latest()->limit(20)->get();
        $plans = SubscriptionPlan::orderBy('price')->get();

        $documents = array_filter([
            'driver_license' => $provider->driver_license,
            'taxi_operator_license' => $provider->taxi_operator_license,
            'vehicle_registration' => $provider->vehicle_registration,
            'insurance_card' => $provider->insurance_card,
            'profile_picture' => $provider->profile_picture,
            'avatar' => $provider->avatar,
        ]);

        return view('admin.providers.show', compact('provider', 'services', 'reviews', 'jobs', 'plans', 'documents'));
    }

    public function updateDocumentStatus(Request $request, $id)
    {
        $data = $request->validate([
            'document_status' => 'required|in:pending,approved,rejected,more_info',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $provider = User::where('role', 'driver')->findOrFail($id);
        $provider->document_status = $data['document_status'];
        if (array_key_exists('admin_notes', $data)) {
            $provider->admin_notes = $data['admin_notes'];
        }

        if ($data['document_status'] === 'approved') {
            $provider->is_active = true;
            $provider->status = 1;
        } elseif ($data['document_status'] === 'rejected') {
            $provider->is_active = false;
        }

        $provider->save();

        $messages = [
            'approved' => 'Provider application approved.',
            'rejected' => 'Provider application denied.',
            'more_info' => 'Additional information requested.',
            'pending' => 'Provider set to pending.',
        ];

        return back()->with('success', $messages[$data['document_status']]);
    }

    public function toggleStatus($id)
    {
        $provider = User::where('role', 'driver')->findOrFail($id);
        $provider->is_active = !(bool) $provider->is_active;
        $provider->save();

        return back()->with('success', $provider->is_active ? 'Provider unsuspended.' : 'Provider suspended.');
    }

    public function ban($id)
    {
        $provider = User::where('role', 'driver')->findOrFail($id);
        $provider->is_banned = !(bool) $provider->is_banned;
        if ($provider->is_banned) {
            $provider->is_active = false;
            $provider->tokens()->delete();
        }
        $provider->save();

        return back()->with('success', $provider->is_banned ? 'Provider banned.' : 'Provider unbanned.');
    }

    public function updateSubscription(Request $request, $id)
    {
        $data = $request->validate([
            'subscription_plan' => 'nullable|string|max:100',
            'subscription_status' => 'required|in:none,active,expired',
            'subscription_expires_at' => 'nullable|date',
        ]);

        $provider = User::where('role', 'driver')->findOrFail($id);
        $provider->update($data);

        return back()->with('success', 'Subscription updated successfully.');
    }

    public function sendExpiryReminder($id)
    {
        $provider = User::where('role', 'driver')->findOrFail($id);

        if ($provider->subscription_status !== 'active') {
            return back()->with('error', 'Provider does not have an active subscription.');
        }

        return back()->with('success', 'Expiry reminder sent to ' . $provider->email);
    }
}
