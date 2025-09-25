<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Dispute;
use App\Models\User;
use App\Models\BookingRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
class DisputeController extends Controller
{
    public function store(Request $request)
    {
        $user = auth()->user();
        $driver = User::where(['id'=>$request->driver_id])->first();
        $validator = Validator::make($request->all(), [
            'booking_id'  => 'required|exists:booking_requests,id',
            'driver_id'   => 'required|exists:users,id',
            'reason'      => 'required|string',
            'description' => 'nullable|string'
        ]);
        if($user->role != 'user'){
            return response()->json([
                'success' => 0,
                'message' => "Only users can dispute"
            ], 400);
        }
        if ($validator->fails()) {
            return response()->json([
                'success' => 0,
                'message' => $validator->errors()->first()
            ], 400);
        }
        $booking = BookingRequest::where(['id'=>$request->booking_id,'status'=>'Completed'])->first();
        if(!$booking){
            return response()->json([
                'success' => 0,
                'message' => 'Booking not completed yet.'
            ], 400);
        }
        if (strtolower($request->reason) === 'other' && empty($request->description)) {
            return response()->json([
                'success' => 0,
                'message' => 'Description is required when reason is Other.'
            ], 400);
        }

        $dispute = Dispute::create([
            'user_id'     => auth()->id(),
            'driver_id'   => $request->driver_id,
            'booking_id'  => $request->booking_id,
            'reason'      => $request->reason,
            'description' => $request->description,
            'status'      => 'pending'
        ]);
        try {
            Http::withoutVerifying()->post(env('SOCKET_SERVER_URL').'/emit/dispute-raised', [
                'driver_id' => $request->driver_id,
                'user_id'   => auth()->id(),
                'booking_id'=> $request->booking_id,
                'reason'    => $request->reason,
                'description' => $request->description,
                'username'=>$booking->user->name ?? trim(($booking->user->first_name ?? '') . ' ' . ($booking->user->last_name ?? '')),
                'drivername'=>$driver->name ?? trim(($driver->first_name ?? '') . ' ' . ($driver->last_name ?? ''))?: $driver->business_name,
            ]);
        } catch (\Exception $e) {
            \Log::error("❌ Socket emit failed: " . $e->getMessage());
        }
        return response()->json([
            'success' => 1,
            'message' => 'Dispute submitted successfully',
            'data'    => $dispute
        ], 200);
    }
    public function getDisputes(Request $request)
    {
        $userId = auth()->id(); // current logged-in user
    
        $disputes = Dispute::where(['user_id'=>$userId,'status'=>'completed'])
            ->with([
                'booking' => function ($q) {
                    $q->select('id', 'status', 'driver_id', 'user_id','ride_time','request_type');
                },
                'booking.driver' => function ($q) {
                    $q->select('id', 'first_name', 'last_name','name','business_name', 'avatar');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    
        if ($disputes->isEmpty()) {
            return response()->json([
                'success' => 0,
                'message' => 'No disputes found',
            ], 404);
        }
    
        return response()->json([
            'success' => 1,
            'message' => 'Disputes retrieved successfully',
            'data' => $disputes,
        ]);
    }
    
}
