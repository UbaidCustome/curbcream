<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalCustomers = User::where('role', 'user')->count();
        $totalProviders = User::where('role', 'driver')->count();
        $activeProviders = User::where('role', 'driver')->where('is_active', 1)->where('is_banned', false)->count();
        $inactiveProviders = User::where('role', 'driver')
            ->where(function ($q) {
                $q->where('is_active', 0)->orWhere('is_banned', true);
            })->count();
        $pendingApprovals = User::where('role', 'driver')->whereIn('document_status', ['pending', 'more_info'])->count();
        $ongoingJobs = Booking::whereIn('status', ['Pending', 'Accepted', 'On Going'])->count();
        $completedJobs = Booking::where('status', 'Completed')->count();
        $activeSubscriptions = User::where('role', 'driver')->where('subscription_status', 'active')->count();
        $expiredSubscriptions = User::where('role', 'driver')->where('subscription_status', 'expired')->count();
        $expiringSoon = User::where('role', 'driver')
            ->where('subscription_status', 'active')
            ->whereNotNull('subscription_expires_at')
            ->whereBetween('subscription_expires_at', [now(), now()->addDays(14)])
            ->count();

        $subscriptionRevenue = SubscriptionPlan::where('is_active', true)->get()->sum(function ($plan) {
            $subscribers = User::where('role', 'driver')
                ->where('subscription_plan', $plan->name)
                ->where('subscription_status', 'active')
                ->count();
            $price = (float) $plan->price;
            $discount = (float) $plan->discount_percent;
            return $subscribers * ($price * (1 - $discount / 100));
        });

        return view('admin.dashboard', [
            'stats' => [
                'total_users' => $totalCustomers + $totalProviders,
                'total_customers' => $totalCustomers,
                'total_providers' => $totalProviders,
                'active_providers' => $activeProviders,
                'inactive_providers' => $inactiveProviders,
                'pending_approvals' => $pendingApprovals,
                'ongoing_jobs' => $ongoingJobs,
                'completed_jobs' => $completedJobs,
                'subscription_revenue' => round($subscriptionRevenue, 2),
                'active_subscriptions' => $activeSubscriptions,
                'expired_subscriptions' => $expiredSubscriptions,
                'expiring_soon' => $expiringSoon,
            ],
            'topServices' => Product::selectRaw('name, COUNT(*) as listings')
                ->groupBy('name')->orderByDesc('listings')->limit(5)->get(),
            'topLocations' => User::where('role', 'driver')
                ->whereNotNull('location')->where('location', '!=', '')
                ->selectRaw('location, COUNT(*) as providers')
                ->groupBy('location')->orderByDesc('providers')->limit(5)->get(),
            'pendingProviders' => User::where('role', 'driver')
                ->whereIn('document_status', ['pending', 'more_info'])
                ->latest()->limit(5)->get(),
            'openDisputes' => Dispute::with(['user', 'driver'])
                ->whereIn('status', ['pending', 'on-going', 'high-priority'])
                ->latest()->limit(5)->get(),
            'featuredListings' => Product::with('user')->where('is_featured', true)->latest()->limit(5)->get(),
        ]);
    }
}
