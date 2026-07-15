@extends('admin.layouts.app')

@section('title', 'Providers')

@section('content')
    <div class="mb-4">
        <h1 class="display-font h3 mb-1">Service Provider Management</h1>
        <p class="text-secondary mb-0">Documents, performance, subscriptions, and compliance</p>
    </div>

    <div class="panel mb-3">
        <div class="panel-body">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                @foreach([
                    'all' => 'All',
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                    'subscription_active' => 'Active Sub',
                    'subscription_expired' => 'Expired Sub',
                ] as $key => $label)
                    <a href="{{ route('admin.providers.index', ['filter' => $key, 'search' => $search]) }}"
                       class="filter-chip {{ $filter === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="search" name="search" value="{{ $search }}" class="form-control ms-auto" style="max-width:260px" placeholder="Search providers...">
                <button class="btn btn-success" style="background:#2bb673;border:0">Search</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Docs</th>
                        <th>Rating</th>
                        <th>Jobs</th>
                        <th>Subscription</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providers as $provider)
                        @php $account = $provider->is_banned ? 'banned' : ($provider->is_active ? 'active' : 'inactive'); @endphp
                        <tr>
                            <td>
                                <strong>{{ $provider->business_name ?: $provider->name }}</strong>
                                <div class="small text-secondary">{{ $provider->email }}</div>
                            </td>
                            <td><span class="badge-soft {{ $provider->document_status ?? 'pending' }}">{{ str_replace('_',' ', $provider->document_status ?? 'pending') }}</span></td>
                            <td>{{ $provider->reviews_avg_rating ?? 0 }}</td>
                            <td>{{ $provider->completed_jobs_count ?? 0 }}</td>
                            <td>
                                <div>{{ $provider->subscription_plan ?: '—' }}</div>
                                <span class="badge-soft {{ $provider->subscription_status ?? 'none' }}">{{ $provider->subscription_status ?? 'none' }}</span>
                            </td>
                            <td><span class="badge-soft {{ $account }}">{{ $account }}</span></td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="{{ route('admin.providers.show', $provider->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    @if(in_array($provider->document_status ?? 'pending', ['pending', 'more_info'], true))
                                        <form action="{{ route('admin.providers.document-status', $provider->id) }}" method="POST" class="js-action-form">
                                            @csrf
                                            <input type="hidden" name="document_status" value="approved">
                                            <button class="btn btn-sm btn-success btn-action"><span class="btn-label">Approve</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
                                        </form>
                                        <form action="{{ route('admin.providers.document-status', $provider->id) }}" method="POST" class="js-action-form">
                                            @csrf
                                            <input type="hidden" name="document_status" value="rejected">
                                            <button class="btn btn-sm btn-danger btn-action"><span class="btn-label">Reject</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
                                        </form>
                                    @endif
                                    <form action="{{ route('admin.providers.toggle-status', $provider->id) }}" method="POST" class="js-action-form">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary btn-action"><span class="btn-label">{{ $provider->is_active ? 'Suspend' : 'Unsuspend' }}</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No providers found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($providers->hasPages())
            <div class="panel-body">{{ $providers->links() }}</div>
        @endif
    </div>
@endsection
