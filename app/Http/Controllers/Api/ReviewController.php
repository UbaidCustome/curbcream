<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

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
        if ($user->id == $request->driver_id) {
            return response()->json([
                'success' => 0,
                'message' => 'You cannot review yourself',
            ], 400);
        }
        $driver = User::find($request->driver_id);
        if ($driver->role !== 'driver') {
            return response()->json([
                'success' => 0,
                'message' => 'Selected user is not a driver',
            ], 400);
        }
    
        $alreadyReviewed = Review::where('user_id', $user->id)
                                ->where('driver_id', $request->driver_id)
                                ->exists();

        if ($alreadyReviewed) {
            return response()->json([
                'success' => 0,
                'message' => 'You have already reviewed this driver',
            ], 400);
        }

        // Create new review
        $review = Review::create([
            'user_id'   => $user->id,
            'driver_id' => $request->driver_id,
            'rating'    => $request->rating,
            'review'    => $request->review
        ]);
        
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
            'message'=>'Data Retrieved',
            'data' => [
                'average_rating' => round($averageRating, 1),
                'total_reviews'  => $totalReviews,
                'reviews'        => $reviews,
            ]
        ]);
    }
    public function getDriverRating($driver_id)
    {
        $driver = User::find($driver_id);

        if (!$driver || $driver->role !== 'driver') {
            return response()->json([
                'success' => 0,
                'message' => 'Driver not found',
            ], 404);
        }

        $averageRating = Review::where('driver_id', $driver_id)->avg('rating');
        $totalReviews  = Review::where('driver_id', $driver_id)->count();

        return response()->json([
            'success' => 1,
            'message' => 'Data Retrieved',
            'data' => [
                'average_rating'  => round($averageRating, 1), // e.g. 4.5
                'total_reviews'   => $totalReviews
            ]
        ]);
    }    
}
