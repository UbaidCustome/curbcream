<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookingRequest extends Model
{
    use HasFactory;

    protected $table = 'booking_requests';

    protected $fillable = [
        'user_id',
        'driver_id',
        'request_type',        // Schedule | Choose | Request
        'status',              // Pending | Accepted | On Going | Completed | Rejected
        'ride_time',           // sirf time (HH:MM:SS)
        'lat',
        'lng',
        'location',
        'special_instruction',
    ];

    protected $casts = [
        'ride_time' => 'datetime:H:i', // sirf time cast karega
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
