<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable,HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'otp',
        'otp_expires_at',
        'first_name',
        'last_name',
        'phone',
        'bio',
        'avatar',
        'address',
        'business_name',
        'location',
        'open_time',
        'close_time',
        'profile_picture',
        'vehicle_category',
        'driver_license',
        'taxi_operator_license',
        'vehicle_registration',
        'insurance_card',
        'profile_completed',
        'is_active',
        'is_notification',
        'status'        
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function reviews()
    {
        return $this->hasMany(Review::class, 'driver_id');
    }
    public function favourites()
    {
        return $this->hasMany(Favourite::class);
    }
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'user_id');
    }
    public function driverBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'driver_id');
    }
}
