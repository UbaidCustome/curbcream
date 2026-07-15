@extends('admin.layouts.app')

@section('title', 'Content Management')

@section('content')
    <div class="mb-4">
        <h1 class="display-font h3 mb-1">Content Management</h1>
        <p class="text-secondary mb-0">Update Privacy Policy and Terms & Conditions shown in the mobile app</p>
    </div>

    <div class="row g-3">
        @foreach($types as $type => $label)
            <div class="col-lg-6">
                <div class="panel h-100">
                    <div class="panel-head">
                        <strong class="display-font">{{ $label }}</strong>
                        @if(optional($contents->get($type))->updated_at)
                            <span class="small text-secondary">Updated {{ $contents->get($type)->updated_at->diffForHumans() }}</span>
                        @endif
                    </div>
                    <div class="panel-body">
                        <form action="{{ route('admin.content.update', $type) }}" method="POST" class="js-action-form">
                            @csrf
                            @method('PUT')
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Content</label>
                                <textarea
                                    name="description"
                                    class="form-control"
                                    rows="14"
                                    required
                                    placeholder="Enter {{ strtolower($label) }} content..."
                                >{{ old('description', optional($contents->get($type))->description) }}</textarea>
                            </div>
                            <button class="btn btn-success btn-action" style="background:#2bb673;border:0">
                                <span class="btn-label">Update {{ $label }}</span>
                                <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
