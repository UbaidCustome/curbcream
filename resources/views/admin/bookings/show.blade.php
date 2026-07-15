@extends('admin.layouts.app')

@section('title', 'Job Detail')

@section('content')
    @php
        $statusClass = match($booking->status) {
            'Pending' => 'pending',
            'Accepted', 'On Going' => 'more_info',
            'Completed' => 'approved',
            'Rejected', 'Cancelled' => 'rejected',
            default => 'none',
        };
        $payment = $booking->status === 'Completed'
            ? 'Paid'
            : (($booking->amount ?? 0) > 0 ? 'Pending' : 'Unpaid');
        $canManage = !in_array($booking->status, ['Completed', 'Cancelled', 'Rejected'], true);
    @endphp

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="display-font h3 mb-1">Job #{{ $booking->id }}</h1>
            <p class="text-secondary mb-0">Provider, customer, location, and payment details</p>
        </div>
        <a href="{{ route('admin.bookings.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-8">
            <div class="panel mb-3">
                <div class="panel-head">
                    <strong class="display-font">Job Details</strong>
                    <span class="badge-soft {{ $statusClass }}">{{ $booking->status }}</span>
                </div>
                <div class="panel-body row g-3">
                    <div class="col-md-6">
                        <div class="text-secondary small">Customer</div>
                        <strong>{{ $booking->user->name ?? $booking->passenger_name ?? '—' }}</strong>
                        <div class="small">{{ $booking->user->email ?? '—' }}</div>
                        <div class="small">{{ $booking->user->phone ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small">Provider</div>
                        <strong>{{ $booking->driver->business_name ?? $booking->driver->name ?? '—' }}</strong>
                        <div class="small">{{ $booking->driver->email ?? '—' }}</div>
                        <div class="small">{{ $booking->driver->phone ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small">Location</div>
                        <div>{{ $booking->location ?: '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small">Schedule</div>
                        <div>{{ $booking->ride_date ?: '—' }} · {{ $booking->ride_time ?: '—' }}</div>
                        <div class="small text-secondary">{{ $booking->request_type }} · {{ $booking->distance ?? 0 }} mi</div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small">Payment</div>
                        <div><strong>${{ number_format((float) ($booking->amount ?? 0), 2) }}</strong></div>
                        <span class="badge-soft {{ $payment === 'Paid' ? 'approved' : 'pending' }}">{{ $payment }}</span>
                    </div>
                    <div class="col-md-6">
                        <div class="text-secondary small">Passenger</div>
                        <div>{{ $booking->passenger_name ?: '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <strong class="display-font">Related Disputes</strong>
                    <a href="{{ route('admin.disputes.index') }}" class="small fw-semibold" style="color:#1f8f58">Manage all</a>
                </div>
                <div class="panel-body">
                    @forelse($disputes as $dispute)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between gap-2">
                                <strong>{{ $dispute->reason }}</strong>
                                <span class="badge-soft {{ $dispute->status }}">{{ $dispute->status }}</span>
                            </div>
                            <div class="text-secondary">{{ $dispute->description ?: 'No description' }}</div>
                        </div>
                    @empty
                        <div class="text-secondary">No disputes linked to this job</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel mb-3">
                <div class="panel-head"><strong class="display-font">Reassign Job</strong></div>
                <div class="panel-body">
                    @if($canManage)
                        <form action="{{ route('admin.bookings.reassign', $booking->id) }}" method="POST" class="js-action-form">
                            @csrf
                            <label class="form-label fw-semibold">New provider</label>
                            <select name="driver_id" class="form-select mb-3" required>
                                <option value="">Select provider</option>
                                @foreach($providers as $provider)
                                    <option value="{{ $provider->id }}" @selected((int) $booking->driver_id === (int) $provider->id)>
                                        {{ $provider->business_name ?: $provider->name }} ({{ $provider->email }})
                                    </option>
                                @endforeach
                            </select>
                            <button class="btn btn-success w-100 btn-action" style="background:#2bb673;border:0">
                                <span class="btn-label">Reassign</span>
                                <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span> Saving...</span>
                            </button>
                        </form>
                    @else
                        <div class="text-secondary">This job cannot be reassigned.</div>
                    @endif
                </div>
            </div>

            <div class="panel">
                <div class="panel-head"><strong class="display-font">Cancel Job</strong></div>
                <div class="panel-body">
                    @if($canManage)
                        <form action="{{ route('admin.bookings.cancel', $booking->id) }}" method="POST" class="js-action-form" onsubmit="return confirm('Cancel this job?')">
                            @csrf
                            <label class="form-label fw-semibold">Reason (optional)</label>
                            <textarea name="cancel_reason" class="form-control mb-3" rows="3" placeholder="Why is this job being cancelled?"></textarea>
                            <button class="btn btn-danger w-100 btn-action">
                                <span class="btn-label">Cancel job</span>
                                <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span> Cancelling...</span>
                            </button>
                        </form>
                    @else
                        <div class="text-secondary">This job is already closed.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
