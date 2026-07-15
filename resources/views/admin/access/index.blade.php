@extends('admin.layouts.app')

@section('title', 'Access Control')

@section('content')
    <div class="mb-4">
        <h1 class="display-font h3 mb-1">Roles, Policies & Access</h1>
        <p class="text-secondary mb-0">Manage team permissions, app policies, and login activity</p>
    </div>

    <div class="row g-3 mb-4">
        @foreach([
            ['Team Members', $stats['team_members']],
            ['Successful Logins', $stats['successful_logins']],
            ['Failed Logins', $stats['failed_logins']],
            ['Unauthorized Attempts', $stats['unauthorized']],
        ] as [$label, $value])
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ $value }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="panel">
                <div class="panel-head">
                    <strong class="display-font">Team Roles & Permissions</strong>
                    <button class="btn btn-sm btn-success" style="background:#2bb673;border:0" data-bs-toggle="modal" data-bs-target="#addMemberModal">Add member</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Member</th>
                                <th>Access Level</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($team as $member)
                                <tr>
                                    <td>
                                        <strong>{{ $member->name }}</strong>
                                        <div class="small text-secondary">{{ $member->email }}</div>
                                    </td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $member->admin_access_level ?: 'super_admin') }}</td>
                                    <td>
                                        <span class="badge-soft {{ $member->is_active ? 'approved' : 'inactive' }}">
                                            {{ $member->is_active ? 'active' : 'inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editMember{{ $member->id }}">Edit</button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-secondary py-4">No team members</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($team->hasPages())
                    <div class="panel-body">{{ $team->links() }}</div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Access Levels</strong></div>
                <div class="panel-body">
                    <div class="mb-3"><strong>Super Admin</strong><div class="small text-secondary">Full platform access</div></div>
                    <div class="mb-3"><strong>Support</strong><div class="small text-secondary">Users, disputes, communication support</div></div>
                    <div><strong>Moderator</strong><div class="small text-secondary">Reviews, compliance, content moderation</div></div>
                </div>
            </div>
        </div>
    </div>

    <div class="panel mb-4">
        <div class="panel-head"><strong class="display-font">App Policies</strong></div>
        <div class="panel-body">
            <div class="row g-3">
                @foreach(['terms' => 'Terms & Conditions', 'privacy' => 'Privacy Policy', 'refund' => 'Refund Policy'] as $type => $label)
                    <div class="col-lg-4">
                        <form action="{{ route('admin.access.policies.update', $type) }}" method="POST" class="js-action-form border rounded p-3 h-100">
                            @csrf
                            <label class="form-label fw-semibold">{{ $label }}</label>
                            <textarea name="description" class="form-control mb-2" rows="6" required>{{ optional($policies->get($type))->description }}</textarea>
                            <button class="btn btn-success btn-action" style="background:#2bb673;border:0">
                                <span class="btn-label">Save</span>
                                <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <strong class="display-font">Login Activity & Unauthorized Attempts</strong>
            <div class="d-flex gap-2 flex-wrap">
                @foreach(['' => 'All', 'success' => 'Success', 'failed' => 'Failed', 'unauthorized' => 'Unauthorized'] as $key => $label)
                    <a href="{{ route('admin.access.index', ['status' => $key ?: null]) }}"
                       class="filter-chip {{ request('status', '') === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Status</th>
                        <th>IP</th>
                        <th>Message</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                        @php
                            $statusClass = match($activity->status) {
                                'success' => 'approved',
                                'failed' => 'pending',
                                default => 'rejected',
                            };
                        @endphp
                        <tr>
                            <td>{{ $activity->email ?: ($activity->user->email ?? '—') }}</td>
                            <td><span class="badge-soft {{ $statusClass }}">{{ $activity->status }}</span></td>
                            <td>{{ $activity->ip_address ?: '—' }}</td>
                            <td>{{ $activity->message ?: '—' }}</td>
                            <td>{{ $activity->created_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">No login activity yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($activities->hasPages())
            <div class="panel-body">{{ $activities->links() }}</div>
        @endif
    </div>

    <div class="modal fade" id="addMemberModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.access.members.store') }}" method="POST" class="modal-content js-action-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add team member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input class="form-control mb-2" name="name" placeholder="Full name" required>
                    <input class="form-control mb-2" type="email" name="email" placeholder="Email" required>
                    <select class="form-select mb-2" name="admin_access_level" required>
                        @foreach($accessLevels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <input class="form-control mb-2" type="password" name="password" placeholder="Password" minlength="6" required>
                    <input class="form-control" type="password" name="password_confirmation" placeholder="Confirm password" minlength="6" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success btn-action" style="background:#2bb673;border:0">
                        <span class="btn-label">Create</span>
                        <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @foreach($team as $member)
        <div class="modal fade" id="editMember{{ $member->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form action="{{ route('admin.access.members.update', $member->id) }}" method="POST" class="modal-content js-action-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input class="form-control mb-2" name="name" value="{{ $member->name }}" required>
                        <input class="form-control mb-2" type="email" name="email" value="{{ $member->email }}" required>
                        <select class="form-select mb-2" name="admin_access_level" required>
                            @foreach($accessLevels as $key => $label)
                                <option value="{{ $key }}" @selected(($member->admin_access_level ?: 'super_admin') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <label class="d-flex align-items-center gap-2 mb-2">
                            <input type="checkbox" name="is_active" value="1" @checked($member->is_active)>
                            Active
                        </label>
                        <input class="form-control mb-2" type="password" name="password" placeholder="New password (optional)" minlength="6">
                        <input class="form-control" type="password" name="password_confirmation" placeholder="Confirm new password" minlength="6">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-success btn-action" style="background:#2bb673;border:0">
                            <span class="btn-label">Save</span>
                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
