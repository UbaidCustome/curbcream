<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\LoginActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AccessControlController extends Controller
{
    public function index(Request $request)
    {
        $team = User::where('role', 'admin')
            ->latest()
            ->paginate(10, ['*'], 'team_page');

        $activitiesQuery = LoginActivity::with('user:id,name,email')->latest();

        if ($status = $request->get('status')) {
            $activitiesQuery->where('status', $status);
        }

        $activities = $activitiesQuery->paginate(15, ['*'], 'activity_page')->withQueryString();

        $policies = Content::whereIn('type', ['terms', 'privacy', 'refund'])
            ->orderBy('type')
            ->get()
            ->keyBy('type');

        $stats = [
            'team_members' => User::where('role', 'admin')->count(),
            'failed_logins' => LoginActivity::where('status', 'failed')->count(),
            'unauthorized' => LoginActivity::where('status', 'unauthorized')->count(),
            'successful_logins' => LoginActivity::where('status', 'success')->count(),
        ];

        $accessLevels = [
            'super_admin' => 'Super Admin',
            'support' => 'Support',
            'moderator' => 'Moderator',
        ];

        return view('admin.access.index', compact(
            'team',
            'activities',
            'policies',
            'stats',
            'accessLevels'
        ));
    }

    public function storeMember(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'admin_access_level' => 'required|in:super_admin,support,moderator',
        ]);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
            'admin_access_level' => $data['admin_access_level'],
            'is_verified' => 1,
            'is_active' => 1,
            'profile_completed' => 1,
            'status' => 1,
            'document_status' => 'approved',
            'is_banned' => false,
        ]);

        return back()->with('success', 'Team member added.');
    }

    public function updateMember(Request $request, $id)
    {
        $member = User::where('role', 'admin')->findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($member->id)],
            'admin_access_level' => 'required|in:super_admin,support,moderator',
            'is_active' => 'nullable|boolean',
            'password' => 'nullable|min:6|confirmed',
        ]);

        $member->name = $data['name'];
        $member->email = $data['email'];
        $member->admin_access_level = $data['admin_access_level'];
        $member->is_active = $request->boolean('is_active');

        if (!empty($data['password'])) {
            $member->password = Hash::make($data['password']);
        }

        $member->save();

        return back()->with('success', 'Team member updated.');
    }

    public function updatePolicy(Request $request, $type)
    {
        if (!in_array($type, ['terms', 'privacy', 'refund'], true)) {
            return back()->with('error', 'Invalid policy type.');
        }

        $data = $request->validate([
            'description' => 'required|string',
        ]);

        Content::updateOrCreate(
            ['type' => $type],
            ['description' => $data['description']]
        );

        return back()->with('success', 'Policy updated successfully.');
    }
}
