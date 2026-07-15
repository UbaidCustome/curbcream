@extends('admin.layouts.app')

@section('title', 'Disputes')

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="display-font h3 mb-1">Dispute Reports</h1>
            <p class="text-secondary mb-0">Review and resolve customer / provider disputes</p>
        </div>
        <form method="GET">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All statuses</option>
                @foreach(['pending','on-going','high-priority','resolved','rejected'] as $item)
                    <option value="{{ $item }}" @selected($status === $item)>{{ $item }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Reason</th>
                        <th>Customer</th>
                        <th>Provider</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($disputes as $dispute)
                        <tr>
                            <td>
                                <strong>{{ $dispute->reason }}</strong>
                                <div class="small text-secondary">{{ $dispute->description ?: '—' }}</div>
                            </td>
                            <td>{{ $dispute->user->name ?? '—' }}</td>
                            <td>{{ $dispute->driver->business_name ?? $dispute->driver->name ?? '—' }}</td>
                            <td><span class="badge-soft {{ $dispute->status }}">{{ $dispute->status }}</span></td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <form action="{{ route('admin.disputes.resolve', $dispute->id) }}" method="POST" class="js-action-form">
                                        @csrf
                                        <input type="hidden" name="status" value="resolved">
                                        <button class="btn btn-sm btn-success btn-action"><span class="btn-label">Resolve</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
                                    </form>
                                    <form action="{{ route('admin.disputes.resolve', $dispute->id) }}" method="POST" class="js-action-form">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <button class="btn btn-sm btn-danger btn-action"><span class="btn-label">Reject</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
                                    </form>
                                    <form action="{{ route('admin.disputes.resolve', $dispute->id) }}" method="POST" class="js-action-form">
                                        @csrf
                                        <input type="hidden" name="status" value="high-priority">
                                        <button class="btn btn-sm btn-outline-warning btn-action"><span class="btn-label">High priority</span><span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">No disputes found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($disputes->hasPages())
            <div class="panel-body">{{ $disputes->links() }}</div>
        @endif
    </div>
@endsection
