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
    
        // Check if driver role hai
        $driver = User::find($request->driver_id);
        if ($driver->role !== 'driver') {
            return response()->json([
                'success' => 0,
                'message' => 'Selected user is not a driver',
            ], 400);
        }
    
        // Prevent duplicate favourites
        $exists = Favourite::where('user_id', $user->id)
                            ->where('driver_id', $request->driver_id)
                            ->exists();
    
        if ($exists) {
            return response()->json([
                'success' => 0,
                'message' => 'Driver already in favourites',
            ], 400);
        }
    
        Favourite::create([
            'user_id' => $user->id,
            'driver_id' => $request->driver_id,
        ]);
    
        return response()->json([
            'success' => 1,
            'message' => 'Driver added to favourites',
        ],201);
    }
    public function getFavourites()
    {
        $user = auth()->user();
    
        $favourites = Favourite::with('driver')
            ->where('user_id', $user->id)
            ->get()
            ->pluck('driver'); // Sirf driver ki info chahiye
    
        return response()->json([
            'success' => 1,
            'message'=>'Data Retrieved Successfully',
            'data' => $favourites
        ]);
    }    
}
