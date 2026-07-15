@extends('admin.layouts.app')

@section('title', 'Analytics')

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="display-font h3 mb-1">Reports & Analytics</h1>
            <p class="text-secondary mb-0">Realtime platform metrics and business exports</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.analytics.export', ['format' => 'csv']) }}" class="btn btn-outline-secondary">Export Excel (CSV)</a>
            <a href="{{ route('admin.analytics.export', ['format' => 'pdf']) }}" class="btn btn-success" style="background:#2bb673;border:0" target="_blank">Export PDF</a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="label">Daily Active Users</div>
                <div class="value">{{ $activeUsers['daily'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="label">Weekly Active Users</div>
                <div class="value">{{ $activeUsers['weekly'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="label">Monthly Active Users</div>
                <div class="value">{{ $activeUsers['monthly'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="label">Avg Job Completion</div>
                <div class="value">{{ $avgCompletionHours }}h</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="label">Avg Rating</div>
                <div class="value">{{ $reviewStats['avg_rating'] }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="label">Total Reviews</div>
                <div class="value">{{ $reviewStats['total'] }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Most Booked Services</strong></div>
                <div class="panel-body">
                    @forelse($mostBookedServices as $service)
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ $service->name }}</span>
                            <strong>{{ $service->jobs_count }} jobs</strong>
                        </div>
                    @empty
                        <div class="text-secondary">No service booking data yet</div>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="panel h-100">
                <div class="panel-head"><strong class="display-font">Peak Booking Hours</strong></div>
                <div class="panel-body">
                    @forelse($peakHours as $row)
                        <div class="d-flex justify-content-between mb-2">
                            <span>{{ str_pad($row->hour, 2, '0', STR_PAD_LEFT) }}:00</span>
                            <strong>{{ $row->total }} bookings</strong>
                        </div>
                    @empty
                        <div class="text-secondary">No peak hour data yet</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><strong class="display-font">Provider Performance Metrics</strong></div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Completed Jobs</th>
                        <th>Avg Rating</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providerPerformance as $provider)
                        <tr>
                            <td>
                                <strong>{{ $provider->business_name ?: $provider->name }}</strong>
                                <div class="small text-secondary">{{ $provider->email }}</div>
                            </td>
                            <td>{{ $provider->completed_jobs }}</td>
                            <td>{{ $provider->avg_rating }}</td>
                            <td>
                                <span class="badge-soft {{ $provider->is_active ? 'approved' : 'inactive' }}">
                                    {{ $provider->is_active ? 'active' : 'inactive' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-secondary py-4">No provider metrics yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
