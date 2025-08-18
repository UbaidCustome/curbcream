<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\User;
class ReviewController extends Controller
{
    public function submitReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|exists:users,id',
            'rating'    => 'required|numeric|min:1|max:5',
            'review'    => 'nullable|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first(),
            ], 400);
        }
    
        $user = auth()->user();
    
        $driver = User::find($request->driver_id);
        if ($driver->role !== 'driver') {
            return response()->json([
                'success' => 0,
                'message' => 'Selected user is not a driver',
            ], 400);
        }
    
        // User can update review if already given
        $review = Review::updateOrCreate(
            ['user_id' => $user->id, 'driver_id' => $request->driver_id],
            ['rating' => $request->rating, 'review' => $request->review]
        );
    
        return response()->json([
            'success' => 1,
            'message' => 'Review submitted successfully',
            'data'    => $review
        ]);
    }
    public function getDriverReviews($driver_id)
    {
        $driver = User::find($driver_id);
    
        if (!$driver || $driver->role !== 'driver') {
            return response()->json([
                'success' => 0,
                'message' => 'Driver not found',
            ], 404);
        }
    
        $reviews = Review::with('user')
                    ->where('driver_id', $driver_id)
                    ->latest()
                    ->get();
    
        $averageRating = Review::where('driver_id', $driver_id)->avg('rating');
        $totalReviews  = Review::where('driver_id', $driver_id)->count();
    
        return response()->json([
            'success' => 1,
            'data' => [
                'average_rating' => round($averageRating, 1),
                'total_reviews'  => $totalReviews,
                'reviews'        => $reviews,
            ]
        ]);
    }    
}
