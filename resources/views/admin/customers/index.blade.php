@extends('admin.layouts.app')

@section('title', 'Customers')

@section('content')
    <div class="mb-4">
        <h1 class="display-font h3 mb-1">Customer Management</h1>
        <p class="text-secondary mb-0">Filter, review, and manage customer accounts</p>
    </div>

    <div class="panel mb-3">
        <div class="panel-body">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                @foreach(['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive', 'new' => 'New users'] as $key => $label)
                    <a href="{{ route('admin.customers.index', ['filter' => $key, 'search' => $search]) }}"
                       class="filter-chip {{ $filter === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="search" name="search" value="{{ $search }}" class="form-control ms-auto" style="max-width:260px" placeholder="Search name, email, phone...">
                <button class="btn btn-success" style="background:#2bb673;border:0">Search</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Bookings</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        @php
                            $status = $customer->is_banned ? 'banned' : ($customer->is_active ? 'active' : 'inactive');
                            $name = $customer->name ?: trim(($customer->first_name ?? '').' '.($customer->last_name ?? ''));
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $name ?: '—' }}</strong>
                                <div class="small text-secondary">{{ $customer->email }}</div>
                            </td>
                            <td>{{ $customer->phone ?: '—' }}</td>
                            <td>{{ $customer->address ?: ($customer->location ?: '—') }}</td>
                            <td>{{ $customer->bookings_count }}</td>
                            <td><span class="badge-soft {{ $status }}">{{ $status }}</span></td>
                            <td>
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="{{ route('admin.customers.show', $customer->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                    <form action="{{ route('admin.customers.toggle-status', $customer->id) }}" method="POST" class="js-action-form">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-primary btn-action">
                                            <span class="btn-label">{{ $customer->is_active ? 'Deactivate' : 'Activate' }}</span>
                                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.customers.reset-password', $customer->id) }}" method="POST" class="js-action-form" onsubmit="return confirm('Reset this customer password?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning btn-action">
                                            <span class="btn-label">Reset PW</span>
                                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.customers.ban', $customer->id) }}" method="POST" class="js-action-form" onsubmit="return confirm('{{ $customer->is_banned ? 'Unban' : 'Ban' }} this customer?')">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-danger btn-action">
                                            <span class="btn-label">{{ $customer->is_banned ? 'Unban' : 'Ban' }}</span>
                                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No customers found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="panel-body">{{ $customers->links() }}</div>
        @endif
    </div>
@endsection
