<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Dispute;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');
        $search = $request->get('search');

        $query = Booking::with(['user', 'driver']);

        if ($filter === 'pending') {
            $query->where('status', 'Pending');
        } elseif ($filter === 'ongoing') {
            $query->whereIn('status', ['Accepted', 'On Going']);
        } elseif ($filter === 'completed') {
            $query->where('status', 'Completed');
        } elseif ($filter === 'cancelled') {
            $query->whereIn('status', ['Rejected', 'Cancelled']);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('passenger_name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('driver', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%")
                            ->orWhere('business_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $bookings = $query->latest()->paginate(15)->withQueryString();

        $counts = [
            'all' => Booking::count(),
            'pending' => Booking::where('status', 'Pending')->count(),
            'ongoing' => Booking::whereIn('status', ['Accepted', 'On Going'])->count(),
            'completed' => Booking::where('status', 'Completed')->count(),
            'cancelled' => Booking::whereIn('status', ['Rejected', 'Cancelled'])->count(),
        ];

        $busiestCategories = Product::query()
            ->select('products.name', DB::raw('COUNT(bookings.id) as jobs_count'))
            ->leftJoin('bookings', function ($join) {
                $join->on('bookings.driver_id', '=', 'products.user_id')
                    ->where('bookings.status', '=', 'Completed');
            })
            ->groupBy('products.name')
            ->orderByDesc('jobs_count')
            ->limit(8)
            ->get();

        $openDisputes = Dispute::with(['user', 'driver'])
            ->whereIn('status', ['pending', 'on-going', 'high-priority'])
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.bookings.index', compact(
            'bookings',
            'filter',
            'search',
            'counts',
            'busiestCategories',
            'openDisputes'
        ));
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'driver'])->findOrFail($id);

        $disputes = Dispute::with(['user', 'driver'])
            ->where(function ($q) use ($booking) {
                $q->where('booking_id', $booking->id)
                    ->orWhere(function ($inner) use ($booking) {
                        $inner->where('user_id', $booking->user_id)
                            ->where('driver_id', $booking->driver_id);
                    });
            })
            ->latest()
            ->get();

        $providers = User::where('role', 'driver')
            ->where('is_banned', false)
            ->where('is_active', 1)
            ->where('document_status', 'approved')
            ->orderBy('business_name')
            ->get(['id', 'name', 'business_name', 'email', 'phone']);

        return view('admin.bookings.show', compact('booking', 'disputes', 'providers'));
    }

    public function cancel(Request $request, $id)
    {
        $data = $request->validate([
            'cancel_reason' => 'nullable|string|max:1000',
        ]);

        $booking = Booking::findOrFail($id);

        if (in_array($booking->status, ['Completed', 'Cancelled', 'Rejected'], true)) {
            return back()->with('error', 'This job can no longer be cancelled.');
        }

        $booking->status = 'Cancelled';
        $booking->save();

        return back()->with('success', 'Job cancelled successfully.' . (!empty($data['cancel_reason']) ? ' Reason saved.' : ''));
    }

    public function reassign(Request $request, $id)
    {
        $data = $request->validate([
            'driver_id' => 'required|exists:users,id',
        ]);

        $booking = Booking::findOrFail($id);

        if (in_array($booking->status, ['Completed', 'Cancelled', 'Rejected'], true)) {
            return back()->with('error', 'This job can no longer be reassigned.');
        }

        $driver = User::where('role', 'driver')
            ->where('id', $data['driver_id'])
            ->where('is_banned', false)
            ->first();

        if (!$driver) {
            return back()->with('error', 'Selected provider is not available.');
        }

        if ((int) $booking->driver_id === (int) $driver->id) {
            return back()->with('error', 'Job is already assigned to this provider.');
        }

        $booking->driver_id = $driver->id;
        if ($booking->status === 'Pending') {
            $booking->status = 'Accepted';
        }
        $booking->save();

        return back()->with('success', 'Job reassigned to ' . ($driver->business_name ?: $driver->name) . '.');
    }
}
