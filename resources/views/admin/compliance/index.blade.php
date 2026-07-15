@extends('admin.layouts.app')

@section('title', 'Compliance')

@section('content')
    <div class="mb-4">
        <h1 class="display-font h3 mb-1">Ratings, Reviews & Compliance</h1>
        <p class="text-secondary mb-0">Moderate reviews, handle violations, and manage terms</p>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Total Reviews', $stats['total_reviews']],
            ['Flagged', $stats['flagged_reviews']],
            ['Removed', $stats['removed_reviews']],
            ['Banned Accounts', $stats['banned_accounts']],
        ] as [$label, $value])
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="panel mb-4">
        <div class="panel-head">
            <strong class="display-font">Review Moderation</strong>
            <div class="d-flex gap-2 flex-wrap">
                @foreach(['all' => 'All', 'flagged' => 'Flagged', 'removed' => 'Removed', 'visible' => 'Visible'] as $key => $label)
                    <a href="{{ route('admin.compliance.index', ['filter' => $key]) }}" class="filter-chip {{ $filter === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Review</th>
                        <th>Customer</th>
                        <th>Provider</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reviews as $review)
                        <tr>
                            <td>
                                <strong>{{ $review->rating }}/5</strong>
                                <div>{{ $review->review ?: 'No comment' }}</div>
                                @if($review->admin_response)
                                    <div class="small text-success mt-1">Admin: {{ $review->admin_response }}</div>
                                @endif
                            </td>
                            <td>{{ $review->user->name ?? '—' }}</td>
                            <td>{{ $review->driver->business_name ?? $review->driver->name ?? '—' }}</td>
                            <td>
                                <span class="badge-soft {{ ($review->moderation_status ?? 'visible') === 'removed' ? 'rejected' : 'approved' }}">
                                    {{ $review->moderation_status ?? 'visible' }}
                                </span>
                                @if($review->is_flagged)
                                    <span class="badge-soft pending">flagged</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <form action="{{ route('admin.compliance.reviews.flag', $review->id) }}" method="POST" class="js-action-form">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning btn-action">
                                            <span class="btn-label">{{ $review->is_flagged ? 'Unflag' : 'Flag' }}</span>
                                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.compliance.reviews.remove', $review->id) }}" method="POST" class="js-action-form">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger btn-action">
                                            <span class="btn-label">{{ ($review->moderation_status ?? 'visible') === 'removed' ? 'Restore' : 'Remove' }}</span>
                                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#respond{{ $review->id }}">Respond</button>
                                    @if($review->user)
                                        <form action="{{ route('admin.compliance.users.ban', $review->user_id) }}" method="POST" class="js-action-form" onsubmit="return confirm('Ban/unban this customer?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-dark btn-action"><span class="btn-label">Ban customer</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
                                        </form>
                                    @endif
                                    @if($review->driver)
                                        <form action="{{ route('admin.compliance.users.ban', $review->driver_id) }}" method="POST" class="js-action-form" onsubmit="return confirm('Ban/unban this provider?')">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-dark btn-action"><span class="btn-label">Ban provider</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">No reviews found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($reviews->hasPages())
            <div class="panel-body">{{ $reviews->links() }}</div>
        @endif
    </div>

    @foreach($reviews as $review)
        <div class="modal fade" id="respond{{ $review->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form action="{{ route('admin.compliance.reviews.respond', $review->id) }}" method="POST" class="modal-content js-action-form">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Respond to review</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <textarea name="admin_response" class="form-control" rows="4" required>{{ $review->admin_response }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-success btn-action" style="background:#2bb673;border:0">
                            <span class="btn-label">Save response</span>
                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-head">
                    <strong class="display-font">Open Complaints</strong>
                    <a href="{{ route('admin.disputes.index') }}" class="small fw-semibold" style="color:#1f8f58">View all</a>
                </div>
                <div class="panel-body">
                    @forelse($disputes as $dispute)
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
                        <div class="text-secondary">No open complaints</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Banned Accounts</strong></div>
                <div class="panel-body">
                    @forelse($bannedUsers as $user)
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2 gap-2">
                            <div>
                                <strong>{{ $user->business_name ?: $user->name }}</strong>
                                <div class="small text-secondary">{{ $user->role }} · {{ $user->email }}</div>
                            </div>
                            <form action="{{ route('admin.compliance.users.ban', $user->id) }}" method="POST" class="js-action-form">
                                @csrf
                                <button class="btn btn-sm btn-outline-success btn-action">
                                    <span class="btn-label">Unban</span>
                                    <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="text-secondary">No banned accounts</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><strong class="display-font">Terms & Conditions Updates</strong></div>
        <div class="panel-body">
            <div class="row g-3">
                @foreach(['terms' => 'Terms & Conditions', 'privacy' => 'Privacy Policy', 'refund' => 'Refund Policy'] as $type => $label)
                    <div class="col-lg-4">
                        <form action="{{ route('admin.compliance.policies.update', $type) }}" method="POST" class="js-action-form border rounded p-3 h-100">
                            @csrf
                            <label class="form-label fw-semibold">{{ $label }}</label>
                            <textarea name="description" class="form-control mb-2" rows="6" required>{{ old('description', optional($policies->get($type))->description) }}</textarea>
                            <button class="btn btn-success btn-action" style="background:#2bb673;border:0">
                                <span class="btn-label">Update</span>
                                <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
