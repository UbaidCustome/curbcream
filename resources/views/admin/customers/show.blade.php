@extends('admin.layouts.app')

@section('title', 'Customer Detail')

@section('content')
    @php
        $name = $customer->name ?: trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''));
        $status = $customer->is_banned ? 'banned' : ($customer->is_active ? 'active' : 'inactive');
    @endphp

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="display-font h3 mb-1">{{ $name ?: 'Customer' }}</h1>
            <p class="text-secondary mb-0">Customer profile, bookings, reviews, and disputes</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.customers.index') }}" class="btn btn-outline-secondary">Back</a>
            <form action="{{ route('admin.customers.toggle-status', $customer->id) }}" method="POST" class="js-action-form">
                @csrf
                <button class="btn btn-primary btn-action">
                    <span class="btn-label">{{ $customer->is_active ? 'Deactivate' : 'Activate' }}</span>
                    <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                </button>
            </form>
            <form action="{{ route('admin.customers.reset-password', $customer->id) }}" method="POST" class="js-action-form" onsubmit="return confirm('Reset password?')">
                @csrf
                <button class="btn btn-warning btn-action">
                    <span class="btn-label">Reset Password</span>
                    <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                </button>
            </form>
            <form action="{{ route('admin.customers.ban', $customer->id) }}" method="POST" class="js-action-form" onsubmit="return confirm('Continue?')">
                @csrf
                <button class="btn btn-danger btn-action">
                    <span class="btn-label">{{ $customer->is_banned ? 'Unban' : 'Ban' }}</span>
                    <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                </button>
            </form>
        </div>
    </div>

    <div class="panel mb-3">
        <div class="panel-head"><strong class="display-font">Profile</strong></div>
        <div class="panel-body row g-2">
            <div class="col-md-6"><strong>Email:</strong> {{ $customer->email }}</div>
            <div class="col-md-6"><strong>Phone:</strong> {{ $customer->phone ?: '—' }}</div>
            <div class="col-md-6"><strong>Location:</strong> {{ $customer->address ?: ($customer->location ?: '—') }}</div>
            <div class="col-md-6"><strong>Status:</strong> <span class="badge-soft {{ $status }}">{{ $status }}</span></div>
        </div>
    </div>

    <div class="panel mb-3">
        <div class="panel-head"><strong class="display-font">Booking History</strong></div>
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>ID</th><th>Provider</th><th>Location</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($bookingHistory as $booking)
                        <tr>
                            <td>#{{ $booking->id }}</td>
                            <td>{{ $booking->driver->business_name ?? $booking->driver->name ?? '—' }}</td>
                            <td>{{ $booking->location ?: '—' }}</td>
                            <td>${{ $booking->amount ?? 0 }}</td>
                            <td>{{ $booking->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">No bookings yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Reviews & Feedback</strong></div>
                <div class="panel-body">
                    @forelse($reviews as $review)
                        <div class="border-bottom pb-2 mb-2">
                            <strong>Rating: {{ $review->rating }}/5</strong>
                            <div class="text-secondary">{{ $review->review ?: 'No comment' }}</div>
                        </div>
                    @empty
                        <div class="text-secondary">No reviews from this customer</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Complaints & Disputes</strong></div>
                <div class="panel-body">
                    @forelse($disputes as $dispute)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <strong>{{ $dispute->reason }}</strong>
                                <span class="badge-soft {{ $dispute->status }}">{{ $dispute->status }}</span>
                            </div>
                            <div class="small text-secondary">Against: {{ $dispute->driver->business_name ?? $dispute->driver->name ?? '—' }}</div>
                            <div>{{ $dispute->description ?: 'No description' }}</div>
                        </div>
                    @empty
                        <div class="text-secondary">No disputes for this customer</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
