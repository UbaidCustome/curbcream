@php $region = $region ?? null; @endphp
<div class="row g-2">
    <div class="col-md-6">
        <label class="form-label fw-semibold">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $region->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">City</label>
        <input type="text" name="city" class="form-control" value="{{ old('city', $region->city ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">State</label>
        <input type="text" name="state" class="form-control" value="{{ old('state', $region->state ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Country</label>
        <input type="text" name="country" class="form-control" value="{{ old('country', $region->country ?? 'US') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label fw-semibold">Radius (km)</label>
        <input type="number" name="radius_km" class="form-control" min="1" max="500" value="{{ old('radius_km', $region->radius_km ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Center latitude</label>
        <input type="number" step="0.0000001" name="center_lat" class="form-control" value="{{ old('center_lat', $region->center_lat ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label fw-semibold">Center longitude</label>
        <input type="number" step="0.0000001" name="center_lng" class="form-control" value="{{ old('center_lng', $region->center_lng ?? '') }}">
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes', $region->notes ?? '') }}</textarea>
    </div>
</div>
