@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="display-font h3 mb-1">Overview</h1>
            <p class="text-secondary mb-0">Platform statistics, analytics, and quick actions</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Total Users', $stats['total_users']],
            ['Customers', $stats['total_customers']],
            ['Providers', $stats['total_providers']],
            ['Active Providers', $stats['active_providers']],
            ['Inactive Providers', $stats['inactive_providers']],
            ['Pending Approvals', $stats['pending_approvals']],
            ['Ongoing Jobs', $stats['ongoing_jobs']],
            ['Completed Jobs', $stats['completed_jobs']],
            ['Expiring Soon', $stats['expiring_soon']],
        ] as [$label, $value])
            <div class="col-6 col-md-4 col-xl-3">
                <div class="stat-card">
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Top Services</strong></div>
                <div class="panel-body">
                    @forelse($topServices as $service)
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ $service->name }}</span>
                            <strong>{{ $service->listings }} listings</strong>
                        </div>
                    @empty
                        <div class="text-secondary">No service analytics yet</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Most Active Locations</strong></div>
                <div class="panel-body">
                    @forelse($topLocations as $location)
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ $location->location }}</span>
                            <strong>{{ $location->providers }} providers</strong>
                        </div>
                    @empty
                        <div class="text-secondary">No location analytics yet</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="panel h-100">
                <div class="panel-head">
                    <strong class="display-font">Pending Applications</strong>
                    <a href="{{ route('admin.providers.index', ['filter' => 'pending']) }}" class="small fw-semibold" style="color:#1f8f58">View all</a>
                </div>
                <div class="panel-body">
                    @forelse($pendingProviders as $provider)
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <div>
                                    <strong>{{ $provider->business_name ?: $provider->name }}</strong>
                                    <div class="small text-secondary">{{ $provider->email }}</div>
                                </div>
                                <span class="badge-soft {{ $provider->document_status }}">{{ str_replace('_', ' ', $provider->document_status) }}</span>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <form action="{{ route('admin.providers.document-status', $provider->id) }}" method="POST" class="js-action-form">
                                    @csrf
                                    <input type="hidden" name="document_status" value="approved">
                                    <button class="btn btn-sm btn-success btn-action">
                                        <span class="btn-label">Approve</span>
                                        <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                    </button>
                                </form>
                                <form action="{{ route('admin.providers.document-status', $provider->id) }}" method="POST" class="js-action-form">
                                    @csrf
                                    <input type="hidden" name="document_status" value="rejected">
                                    <button class="btn btn-sm btn-danger btn-action">
                                        <span class="btn-label">Deny</span>
                                        <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="text-secondary">No pending approvals</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel h-100">
                <div class="panel-head">
                    <strong class="display-font">Dispute Reports</strong>
                    <a href="{{ route('admin.disputes.index') }}" class="small fw-semibold" style="color:#1f8f58">View all</a>
                </div>
                <div class="panel-body">
                    @forelse($openDisputes as $dispute)
                        <div class="border-bottom pb-3 mb-3">
                            <strong>{{ $dispute->reason }}</strong>
                            <div class="small text-secondary mb-2">
                                {{ $dispute->user->name ?? 'Customer' }} vs {{ $dispute->driver->business_name ?? $dispute->driver->name ?? 'Provider' }}
                            </div>
                            <form action="{{ route('admin.disputes.resolve', $dispute->id) }}" method="POST" class="js-action-form">
                                @csrf
                                <input type="hidden" name="status" value="resolved">
                                <button class="btn btn-sm btn-success btn-action">
                                    <span class="btn-label">Resolve</span>
                                    <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="text-secondary">No open disputes</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel h-100">
                <div class="panel-head">
                    <strong class="display-font">Featured Listings</strong>
                    <a href="{{ route('admin.listings.index') }}" class="small fw-semibold" style="color:#1f8f58">Manage</a>
                </div>
                <div class="panel-body">
                    @forelse($featuredListings as $listing)
                        <div class="d-flex justify-content-between align-items-center gap-2 border-bottom pb-3 mb-3">
                            <div>
                                <strong>{{ $listing->name }}</strong>
                                <div class="small text-secondary">${{ $listing->price }} · {{ $listing->user->business_name ?? $listing->user->name ?? '—' }}</div>
                            </div>
                            <form action="{{ route('admin.listings.toggle-featured', $listing->id) }}" method="POST" class="js-action-form">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary btn-action">
                                    <span class="btn-label">Unfeature</span>
                                    <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="text-secondary">No featured listings</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
