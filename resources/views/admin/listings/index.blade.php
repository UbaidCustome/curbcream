@extends('admin.layouts.app')

@section('title', 'Featured Listings')

@section('content')
    <div class="mb-4">
        <h1 class="display-font h3 mb-1">Featured Listings & Promotions</h1>
        <p class="text-secondary mb-0">Manage which service listings are featured</p>
    </div>

    <div class="panel mb-3">
        <div class="panel-body">
            <form method="GET" class="d-flex flex-wrap gap-2">
                <input type="search" name="search" value="{{ $search }}" class="form-control" style="max-width:260px" placeholder="Search listings...">
                <label class="filter-chip {{ $featuredOnly ? 'active' : '' }}">
                    <input type="checkbox" name="featured" value="1" class="d-none" @checked($featuredOnly) onchange="this.form.submit()">
                    Featured only
                </label>
                <button class="btn btn-success" style="background:#2bb673;border:0">Search</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Service</th>
                        <th>Provider</th>
                        <th>Price</th>
                        <th>Featured</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($listings as $listing)
                        <tr>
                            <td><strong>{{ $listing->name }}</strong></td>
                            <td>{{ $listing->user->business_name ?? $listing->user->name ?? '—' }}</td>
                            <td>${{ $listing->price }}</td>
                            <td>{{ $listing->is_featured ? 'Yes' : 'No' }}</td>
                            <td>
                                <form action="{{ route('admin.listings.toggle-featured', $listing->id) }}" method="POST" class="js-action-form">
                                    @csrf
                                    <button class="btn btn-sm {{ $listing->is_featured ? 'btn-outline-secondary' : 'btn-success' }} btn-action">
                                        <span class="btn-label">{{ $listing->is_featured ? 'Unfeature' : 'Feature' }}</span>
                                        <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-secondary py-4">No listings found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($listings->hasPages())
            <div class="panel-body">{{ $listings->links() }}</div>
        @endif
    </div>
@endsection
