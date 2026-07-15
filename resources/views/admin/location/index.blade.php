@extends('admin.layouts.app')

@section('title', 'Location Settings')

@section('content')
    <div class="mb-4">
        <h1 class="display-font h3 mb-1">Location & Availability Settings</h1>
        <p class="text-secondary mb-0">Manage regions, max distance, timezone, and provider online status</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="label">Enabled Regions</div>
                <div class="value">{{ $regions->where('is_enabled', true)->count() }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="label">Disabled Regions</div>
                <div class="value">{{ $regions->where('is_enabled', false)->count() }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="label">Online Providers</div>
                <div class="value">{{ $onlineCount }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="label">Offline Providers</div>
                <div class="value">{{ $offlineCount }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-5">
            <div class="panel h-100">
                <div class="panel-head">
                    <strong class="display-font">Distance & Regional Settings</strong>
                </div>
                <div class="panel-body">
                    <form action="{{ route('admin.location.settings.update') }}" method="POST" class="js-action-form">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Maximum distance for service requests</label>
                            <div class="input-group">
                                <input type="number" step="0.1" min="1" max="500" name="max_service_distance_km" class="form-control" value="{{ old('max_service_distance_km', $settings['max_service_distance_km']) }}" required>
                                <select name="distance_unit" class="form-select" style="max-width:110px">
                                    <option value="km" @selected(old('distance_unit', $settings['distance_unit']) === 'km')>km</option>
                                    <option value="miles" @selected(old('distance_unit', $settings['distance_unit']) === 'miles')>miles</option>
                                </select>
                            </div>
                            <div class="form-text">Used as the max radius for matching nearby trucks.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Timezone</label>
                            <select name="timezone" class="form-select" required>
                                @foreach($timezones as $timezone)
                                    <option value="{{ $timezone }}" @selected(old('timezone', $settings['timezone']) === $timezone)>{{ $timezone }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Regional label</label>
                            <input type="text" name="region_label" class="form-control" value="{{ old('region_label', $settings['region_label']) }}" required>
                        </div>
                        <button class="btn btn-success btn-action" style="background:#2bb673;border:0">
                            <span class="btn-label">Save settings</span>
                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span> Saving...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="panel h-100">
                <div class="panel-head">
                    <strong class="display-font">Service Regions</strong>
                    <button class="btn btn-sm btn-success" style="background:#2bb673;border:0" data-bs-toggle="modal" data-bs-target="#addRegionModal">Add region</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Region</th>
                                <th>Area</th>
                                <th>Radius</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($regions as $region)
                                <tr>
                                    <td>
                                        <strong>{{ $region->name }}</strong>
                                        @if($region->notes)
                                            <div class="small text-secondary">{{ \Illuminate\Support\Str::limit($region->notes, 50) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ collect([$region->city, $region->state, $region->country])->filter()->implode(', ') ?: '—' }}
                                        @if($region->center_lat && $region->center_lng)
                                            <div class="small text-secondary">{{ $region->center_lat }}, {{ $region->center_lng }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $region->radius_km ? $region->radius_km.' km' : '—' }}</td>
                                    <td>
                                        <span class="badge-soft {{ $region->is_enabled ? 'approved' : 'inactive' }}">
                                            {{ $region->is_enabled ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editRegion{{ $region->id }}">Edit</button>
                                            <form action="{{ route('admin.location.regions.toggle', $region->id) }}" method="POST" class="js-action-form">
                                                @csrf
                                                <button class="btn btn-sm {{ $region->is_enabled ? 'btn-outline-warning' : 'btn-outline-success' }} btn-action">
                                                    <span class="btn-label">{{ $region->is_enabled ? 'Disable' : 'Enable' }}</span>
                                                    <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.location.regions.destroy', $region->id) }}" method="POST" class="js-action-form" onsubmit="return confirm('Delete this region?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger btn-action">
                                                    <span class="btn-label">Delete</span>
                                                    <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-secondary py-4">No regions yet</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head">
            <strong class="display-font">Online / Offline Providers</strong>
            <div class="d-flex gap-2 flex-wrap">
                @foreach(['all' => 'All', 'online' => 'Online', 'offline' => 'Offline'] as $key => $label)
                    <a href="{{ route('admin.location.index', ['provider_status' => $key]) }}"
                       class="filter-chip {{ $filter === $key ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Provider</th>
                        <th>Location</th>
                        <th>Coordinates</th>
                        <th>Hours</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providers as $provider)
                        <tr>
                            <td>
                                <strong>{{ $provider->business_name ?: $provider->name }}</strong>
                                <div class="small text-secondary">{{ $provider->email }}</div>
                            </td>
                            <td>{{ $provider->location ?: ($provider->address ?: '—') }}</td>
                            <td>
                                @if($provider->current_lat && $provider->current_lng)
                                    {{ $provider->current_lat }}, {{ $provider->current_lng }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $provider->open_time ?: '—' }} - {{ $provider->close_time ?: '—' }}</td>
                            <td>
                                <span class="badge-soft {{ $provider->is_active ? 'approved' : 'inactive' }}">
                                    {{ $provider->is_active ? 'Online' : 'Offline' }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.providers.show', $provider->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-secondary py-4">No providers found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($providers->hasPages())
            <div class="panel-body">{{ $providers->links() }}</div>
        @endif
    </div>

    <div class="modal fade" id="addRegionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form action="{{ route('admin.location.regions.store') }}" method="POST" class="modal-content js-action-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add service region</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @include('admin.location._region-form')
                    <label class="d-flex align-items-center gap-2 mt-2">
                        <input type="checkbox" name="is_enabled" value="1" checked>
                        Enable region
                    </label>
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

    @foreach($regions as $region)
        <div class="modal fade" id="editRegion{{ $region->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <form action="{{ route('admin.location.regions.update', $region->id) }}" method="POST" class="modal-content js-action-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit region</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @include('admin.location._region-form', ['region' => $region])
                        <label class="d-flex align-items-center gap-2 mt-2">
                            <input type="checkbox" name="is_enabled" value="1" @checked($region->is_enabled)>
                            Enable region
                        </label>
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
