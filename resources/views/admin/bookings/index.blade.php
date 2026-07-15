@extends('admin.layouts.app')

@section('title', 'Bookings')

@section('content')
    <div class="mb-4">
        <h1 class="display-font h3 mb-1">Booking & Job Management</h1>
        <p class="text-secondary mb-0">Monitor live jobs, review disputes, and track busy categories</p>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['All Jobs', $counts['all']],
            ['Pending', $counts['pending']],
            ['Ongoing', $counts['ongoing']],
            ['Completed', $counts['completed']],
            ['Cancelled', $counts['cancelled']],
        ] as [$label, $value])
            <div class="col-6 col-md">
                <div class="stat-card">
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="panel mb-3">
        <div class="panel-body">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                @foreach([
                    'all' => 'All',
                    'pending' => 'Pending',
                    'ongoing' => 'Ongoing',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ] as $key => $label)
                    <a href="{{ route('admin.bookings.index', ['filter' => $key, 'search' => $search]) }}"
                       class="filter-chip {{ $filter === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="search" name="search" value="{{ $search }}" class="form-control ms-auto" style="max-width:280px" placeholder="Search job, customer, provider...">
                <button class="btn btn-success" style="background:#2bb673;border:0">Search</button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="panel">
                <div class="panel-head">
                    <strong class="display-font">Live Job Requests</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Job</th>
                                <th>Customer</th>
                                <th>Provider</th>
                                <th>Location</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookings as $booking)
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
                                @endphp
                                <tr>
                                    <td>
                                        <strong>#{{ $booking->id }}</strong>
                                        <div class="small text-secondary">{{ $booking->request_type }} · {{ $booking->ride_date }}</div>
                                    </td>
                                    <td>
                                        {{ $booking->user->name ?? $booking->passenger_name ?? '—' }}
                                        <div class="small text-secondary">{{ $booking->user->email ?? '' }}</div>
                                    </td>
                                    <td>
                                        {{ $booking->driver->business_name ?? $booking->driver->name ?? '—' }}
                                        <div class="small text-secondary">{{ $booking->driver->email ?? '' }}</div>
                                    </td>
                                    <td style="max-width:180px">{{ \Illuminate\Support\Str::limit($booking->location, 40) }}</td>
                                    <td>
                                        <div>${{ number_format((float) ($booking->amount ?? 0), 2) }}</div>
                                        <span class="badge-soft {{ $payment === 'Paid' ? 'approved' : 'pending' }}">{{ $payment }}</span>
                                    </td>
                                    <td><span class="badge-soft {{ $statusClass }}">{{ $booking->status }}</span></td>
                                    <td>
                                        <a href="{{ route('admin.bookings.show', $booking->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-secondary py-4">No jobs found</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($bookings->hasPages())
                    <div class="panel-body">{{ $bookings->links() }}</div>
                @endif
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel mb-3">
                <div class="panel-head"><strong class="display-font">Busiest Service Categories</strong></div>
                <div class="panel-body">
                    @forelse($busiestCategories as $category)
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ $category->name }}</span>
                            <strong>{{ $category->jobs_count }} jobs</strong>
                        </div>
                    @empty
                        <div class="text-secondary">No category data yet</div>
                    @endforelse
                </div>
            </div>

            <div class="panel">
                <div class="panel-head">
                    <strong class="display-font">Open Disputes</strong>
                    <a href="{{ route('admin.disputes.index') }}" class="small fw-semibold" style="color:#1f8f58">View all</a>
                </div>
                <div class="panel-body">
                    @forelse($openDisputes as $dispute)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between gap-2">
                                <strong>{{ $dispute->reason }}</strong>
                                <span class="badge-soft {{ $dispute->status }}">{{ $dispute->status }}</span>
                            </div>
                            <div class="small text-secondary">
                                {{ $dispute->user->name ?? 'Customer' }} vs {{ $dispute->driver->business_name ?? $dispute->driver->name ?? 'Provider' }}
                            </div>
                        </div>
                    @empty
                        <div class="text-secondary">No open disputes</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
