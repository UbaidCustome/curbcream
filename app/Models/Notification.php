<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'driver_id',
        'booking_id',
        'title',
        'message',
        'is_read',
    ];

    public function booking() {
        return $this->belongsTo(BookingRequest::class, 'booking_id');
    }

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function driver() {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
