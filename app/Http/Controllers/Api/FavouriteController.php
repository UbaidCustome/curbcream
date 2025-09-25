<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Favourite;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class FavouriteController extends Controller
{
    public function addToFavourite(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|exists:users,id',
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
    
        $favourite = Favourite::where('user_id', $user->id)
                              ->where('driver_id', $request->driver_id)
                              ->first();
    
        if ($favourite) {
            $favourite->delete();
    
            return response()->json([
                'success' => 1,
                'message' => 'Driver removed from favourites',
                'is_favourite' => false
            ], 200);
        } else {
            Favourite::create([
                'user_id' => $user->id,
                'driver_id' => $request->driver_id,
            ]);
    
            return response()->json([
                'success' => 1,
                'message' => 'Driver added to favourites',
                'is_favourite' => true
            ], 200);
        }
    }
    public function getFavourites()
    {
        $user = auth()->user();
        
        $favourites = Favourite::with(['driver.reviews'])
            ->where('user_id', $user->id)
            ->get()
            ->map(function($favourite) {
                $driver = $favourite->driver;
                
                // Calculate rating count and average
                $ratingCount = $driver->reviews->count();
                $avgRating = $driver->reviews->avg('rating');
                
                // Add the calculated fields to the driver object
                $driver->reviews_count = $ratingCount;
                $driver->reviews_avg_rating = $avgRating ? (float) number_format($avgRating, 1) : 0;
                
                // Remove the reviews relation if you don't want it in the response
                unset($driver->reviews);
                
                return $driver;
            });
    
        return response()->json([
            'success' => 1,
            'message' => 'Data Retrieved Successfully',
            'data' => $favourites
        ]);
    }  
}
