<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;

class QuickActionController extends Controller
{
    public function disputes(Request $request)
    {
        $status = $request->get('status');
        $query = Dispute::with(['user', 'driver']);
        if ($status) {
            $query->where('status', $status);
        }
        $disputes = $query->latest()->paginate(15)->withQueryString();

        return view('admin.disputes.index', compact('disputes', 'status'));
    }

    public function resolveDispute(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,resolved,rejected,on-going,high-priority',
        ]);

        $dispute = Dispute::findOrFail($id);
        $dispute->status = $data['status'];
        $dispute->save();

        return back()->with('success', 'Dispute updated successfully.');
    }

    public function listings(Request $request)
    {
        $query = Product::with('user');
        if ($request->get('featured') === '1') {
            $query->where('is_featured', true);
        }
        if ($search = $request->get('search')) {
            $query->where('name', 'like', "%{$search}%");
        }
        $listings = $query->latest()->paginate(15)->withQueryString();
        $featuredOnly = $request->get('featured') === '1';
        $search = $request->get('search');

        return view('admin.listings.index', compact('listings', 'featuredOnly', 'search'));
    }

    public function toggleFeatured($id)
    {
        $product = Product::findOrFail($id);
        $product->is_featured = !(bool) $product->is_featured;
        $product->save();

        return back()->with('success', $product->is_featured ? 'Listing featured.' : 'Listing unfeatured.');
    }

    public function plans()
    {
        $plans = SubscriptionPlan::orderBy('price')->get();
        return view('admin.plans.index', compact('plans'));
    }

    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'price' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'is_promotional' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        SubscriptionPlan::create([
            ...$data,
            'discount_percent' => $data['discount_percent'] ?? 0,
            'is_promotional' => $request->boolean('is_promotional'),
            'is_active' => true,
        ]);

        return back()->with('success', 'Plan created successfully.');
    }

    public function updatePlan(Request $request, $id)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'price' => 'required|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'is_promotional' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $plan = SubscriptionPlan::findOrFail($id);
        $plan->update([
            ...$data,
            'discount_percent' => $data['discount_percent'] ?? 0,
            'is_promotional' => $request->boolean('is_promotional'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Plan updated successfully.');
    }
}
