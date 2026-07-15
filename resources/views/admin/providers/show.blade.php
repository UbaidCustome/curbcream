@extends('admin.layouts.app')

@section('title', 'Provider Detail')

@section('content')
    @php $account = $provider->is_banned ? 'banned' : ($provider->is_active ? 'active' : 'inactive'); @endphp

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="display-font h3 mb-1">{{ $provider->business_name ?: $provider->name }}</h1>
            <p class="text-secondary mb-0">Documents, services, performance, and subscription</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.providers.index') }}" class="btn btn-outline-secondary">Back</a>
            <form action="{{ route('admin.providers.toggle-status', $provider->id) }}" method="POST" class="js-action-form">
                @csrf
                <button class="btn btn-primary btn-action"><span class="btn-label">{{ $provider->is_active ? 'Suspend' : 'Unsuspend' }}</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
            </form>
            <form action="{{ route('admin.providers.ban', $provider->id) }}" method="POST" class="js-action-form" onsubmit="return confirm('Continue?')">
                @csrf
                <button class="btn btn-danger btn-action"><span class="btn-label">{{ $provider->is_banned ? 'Unban' : 'Ban' }}</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Profile</strong></div>
                <div class="panel-body">
                    <div class="mb-1"><strong>Email:</strong> {{ $provider->email }}</div>
                    <div class="mb-1"><strong>Phone:</strong> {{ $provider->phone ?: '—' }}</div>
                    <div class="mb-1"><strong>Location:</strong> {{ $provider->location ?: ($provider->address ?: '—') }}</div>
                    <div class="mb-1"><strong>Bio:</strong> {{ $provider->bio ?: '—' }}</div>
                    <div class="mb-1"><strong>Docs:</strong> <span class="badge-soft {{ $provider->document_status ?? 'pending' }}">{{ str_replace('_',' ', $provider->document_status ?? 'pending') }}</span></div>
                    <div><strong>Account:</strong> <span class="badge-soft {{ $account }}">{{ $account }}</span></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Performance</strong></div>
                <div class="panel-body">
                    <div class="mb-1"><strong>Rating:</strong> {{ $provider->reviews_avg_rating ?? 0 }}/5</div>
                    <div class="mb-1"><strong>Completed jobs:</strong> {{ $provider->completed_jobs_count ?? 0 }}</div>
                    <div class="mb-1"><strong>Vehicle:</strong> {{ $provider->vehicle_category ?: '—' }}</div>
                    <div><strong>Hours:</strong> {{ $provider->open_time ?: '—' }} - {{ $provider->close_time ?: '—' }}</div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Subscription</strong></div>
                <div class="panel-body">
                    <form action="{{ route('admin.providers.subscription', $provider->id) }}" method="POST" class="js-action-form mb-3">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Plan</label>
                            <select name="subscription_plan" class="form-select">
                                <option value="">Select plan</option>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->name }}" @selected($provider->subscription_plan === $plan->name)>{{ $plan->name }} (${{ $plan->price }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="subscription_status" class="form-select">
                                @foreach(['none','active','expired'] as $status)
                                    <option value="{{ $status }}" @selected(($provider->subscription_status ?? 'none') === $status)>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold">Expires at</label>
                            <input type="date" name="subscription_expires_at" class="form-control" value="{{ optional($provider->subscription_expires_at)->format('Y-m-d') }}">
                        </div>
                        <button class="btn btn-success btn-action" style="background:#2bb673;border:0">
                            <span class="btn-label">Save subscription</span>
                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </form>
                    <form action="{{ route('admin.providers.send-expiry-reminder', $provider->id) }}" method="POST" class="js-action-form">
                        @csrf
                        <button class="btn btn-outline-secondary btn-action w-100">
                            <span class="btn-label">Send expiry reminder</span>
                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="panel mb-3">
        <div class="panel-head"><strong class="display-font">Document Review</strong></div>
        <div class="panel-body">
            @if(count($documents))
                <div class="row g-2 mb-3">
                    @foreach($documents as $key => $path)
                        <div class="col-md-4 col-lg-3">
                            <a href="{{ asset('storage/'.$path) }}" target="_blank" class="d-block border rounded p-3 text-decoration-none">
                                <div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $key) }}</div>
                                <div class="small" style="color:#1f8f58">Open file</div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-secondary mb-3">No documents uploaded</div>
            @endif

            <form action="{{ route('admin.providers.document-status', $provider->id) }}" method="POST" class="js-action-form">
                @csrf
                <label class="form-label fw-semibold">Admin notes</label>
                <textarea name="admin_notes" class="form-control mb-3" rows="3">{{ old('admin_notes', $provider->admin_notes) }}</textarea>
                <div class="d-flex gap-2 flex-wrap">
                    <button name="document_status" value="approved" class="btn btn-success btn-action"><span class="btn-label">Approve</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
                    <button name="document_status" value="rejected" class="btn btn-danger btn-action"><span class="btn-label">Reject</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
                    <button name="document_status" value="more_info" class="btn btn-outline-primary btn-action"><span class="btn-label">Request more info</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
                </div>
            </form>
        </div>
    </div>

    <div class="panel mb-3">
        <div class="panel-head"><strong class="display-font">Service Listings</strong></div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Name</th><th>Price</th><th>Featured</th></tr></thead>
                <tbody>
                    @forelse($services as $service)
                        <tr>
                            <td>{{ $service->name }}</td>
                            <td>${{ $service->price }}</td>
                            <td>{{ $service->is_featured ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-secondary py-4">No services listed</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Recent Jobs</strong></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead><tr><th>ID</th><th>Customer</th><th>Status</th></tr></thead>
                        <tbody>
                            @forelse($jobs as $job)
                                <tr>
                                    <td>#{{ $job->id }}</td>
                                    <td>{{ $job->user->name ?? '—' }}</td>
                                    <td>{{ $job->status }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-secondary py-4">No jobs yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Reviews</strong></div>
                <div class="panel-body">
                    @forelse($reviews as $review)
                        <div class="border-bottom pb-2 mb-2">
                            <strong>{{ $review->rating }}/5</strong> · {{ $review->user->name ?? 'Customer' }}
                            <div class="text-secondary">{{ $review->review ?: 'No comment' }}</div>
                        </div>
                    @empty
                        <div class="text-secondary">No reviews yet</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection
