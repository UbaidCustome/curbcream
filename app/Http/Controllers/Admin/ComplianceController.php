<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Dispute;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'all');

        $reviewsQuery = Review::with(['user:id,name,email', 'driver:id,name,business_name,email']);

        if ($filter === 'flagged') {
            $reviewsQuery->where('is_flagged', true);
        } elseif ($filter === 'removed') {
            $reviewsQuery->where('moderation_status', 'removed');
        } elseif ($filter === 'visible') {
            $reviewsQuery->where('moderation_status', 'visible');
        }

        $reviews = $reviewsQuery->latest()->paginate(15, ['*'], 'reviews_page')->withQueryString();

        $disputes = Dispute::with(['user:id,name,email', 'driver:id,name,business_name,email'])
            ->whereIn('status', ['pending', 'on-going', 'high-priority'])
            ->latest()
            ->limit(8)
            ->get();

        $bannedUsers = User::whereIn('role', ['user', 'driver'])
            ->where('is_banned', true)
            ->latest()
            ->limit(10)
            ->get();

        $policies = Content::whereIn('type', ['terms', 'privacy', 'refund'])
            ->orderBy('type')
            ->get()
            ->keyBy('type');

        $stats = [
            'total_reviews' => Review::count(),
            'flagged_reviews' => Review::where('is_flagged', true)->count(),
            'removed_reviews' => Review::where('moderation_status', 'removed')->count(),
            'banned_accounts' => User::whereIn('role', ['user', 'driver'])->where('is_banned', true)->count(),
        ];

        return view('admin.compliance.index', compact(
            'reviews',
            'disputes',
            'bannedUsers',
            'policies',
            'stats',
            'filter'
        ));
    }

    public function flagReview($id)
    {
        $review = Review::findOrFail($id);
        $review->is_flagged = !$review->is_flagged;
        $review->save();

        return back()->with('success', $review->is_flagged ? 'Review flagged.' : 'Review unflagged.');
    }

    public function removeReview($id)
    {
        $review = Review::findOrFail($id);
        $review->moderation_status = $review->moderation_status === 'removed' ? 'visible' : 'removed';
        if ($review->moderation_status === 'removed') {
            $review->is_flagged = true;
        }
        $review->save();

        return back()->with(
            'success',
            $review->moderation_status === 'removed' ? 'Review removed.' : 'Review restored.'
        );
    }

    public function respondReview(Request $request, $id)
    {
        $data = $request->validate([
            'admin_response' => 'required|string|max:2000',
        ]);

        $review = Review::findOrFail($id);
        $review->admin_response = $data['admin_response'];
        $review->save();

        return back()->with('success', 'Response saved on review.');
    }

    public function banUser(Request $request, $id)
    {
        $user = User::whereIn('role', ['user', 'driver'])->findOrFail($id);
        $user->is_banned = !$user->is_banned;
        if ($user->is_banned) {
            $user->is_active = false;
            $user->tokens()->delete();
        }
        $user->save();

        return back()->with('success', ($user->role === 'driver' ? 'Provider' : 'Customer') . ($user->is_banned ? ' banned.' : ' unbanned.'));
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

        $labels = [
            'terms' => 'Terms & Conditions',
            'privacy' => 'Privacy Policy',
            'refund' => 'Refund Policy',
        ];

        return back()->with('success', $labels[$type] . ' updated.');
    }
}
