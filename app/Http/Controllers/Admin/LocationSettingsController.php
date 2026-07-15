<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\ServiceRegion;
use App\Models\User;
use Illuminate\Http\Request;

class LocationSettingsController extends Controller
{
    public function index(Request $request)
    {
        $regions = ServiceRegion::orderBy('name')->get();

        $settings = [
            'max_service_distance_km' => PlatformSetting::getValue('max_service_distance_km', '10'),
            'timezone' => PlatformSetting::getValue('timezone', 'America/Chicago'),
            'region_label' => PlatformSetting::getValue('region_label', 'Texas, USA'),
            'distance_unit' => PlatformSetting::getValue('distance_unit', 'km'),
        ];

        $filter = $request->get('provider_status', 'all');
        $providersQuery = User::where('role', 'driver')
            ->where('document_status', 'approved')
            ->where('is_banned', false);

        if ($filter === 'online') {
            $providersQuery->where('is_active', 1);
        } elseif ($filter === 'offline') {
            $providersQuery->where('is_active', 0);
        }

        $providers = $providersQuery
            ->orderByDesc('is_active')
            ->orderBy('business_name')
            ->paginate(15, ['*'], 'providers_page')
            ->withQueryString();

        $onlineCount = User::where('role', 'driver')
            ->where('document_status', 'approved')
            ->where('is_banned', false)
            ->where('is_active', 1)
            ->count();

        $offlineCount = User::where('role', 'driver')
            ->where('document_status', 'approved')
            ->where('is_banned', false)
            ->where('is_active', 0)
            ->count();

        $timezones = [
            'America/New_York',
            'America/Chicago',
            'America/Denver',
            'America/Los_Angeles',
            'America/Phoenix',
            'UTC',
        ];

        return view('admin.location.index', compact(
            'regions',
            'settings',
            'providers',
            'filter',
            'onlineCount',
            'offlineCount',
            'timezones'
        ));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'max_service_distance_km' => 'required|numeric|min:1|max:500',
            'timezone' => 'required|string|max:100',
            'region_label' => 'required|string|max:150',
            'distance_unit' => 'required|in:km,miles',
        ]);

        PlatformSetting::many($data);

        return back()->with('success', 'Location & availability settings updated.');
    }

    public function storeRegion(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'center_lat' => 'nullable|numeric|between:-90,90',
            'center_lng' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|integer|min:1|max:500',
            'notes' => 'nullable|string|max:1000',
            'is_enabled' => 'nullable|boolean',
        ]);

        ServiceRegion::create([
            ...$data,
            'country' => $data['country'] ?? 'US',
            'is_enabled' => $request->boolean('is_enabled', true),
        ]);

        return back()->with('success', 'Service region added.');
    }

    public function updateRegion(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'center_lat' => 'nullable|numeric|between:-90,90',
            'center_lng' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|integer|min:1|max:500',
            'notes' => 'nullable|string|max:1000',
            'is_enabled' => 'nullable|boolean',
        ]);

        $region = ServiceRegion::findOrFail($id);
        $region->update([
            ...$data,
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return back()->with('success', 'Service region updated.');
    }

    public function toggleRegion($id)
    {
        $region = ServiceRegion::findOrFail($id);
        $region->is_enabled = !$region->is_enabled;
        $region->save();

        return back()->with(
            'success',
            $region->is_enabled ? 'Region enabled.' : 'Region disabled.'
        );
    }

    public function destroyRegion($id)
    {
        ServiceRegion::findOrFail($id)->delete();

        return back()->with('success', 'Service region deleted.');
    }
}
