<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Define the fields that are mass-assignable
    protected $fillable = [
        'name', 'price', 'images', 'user_id',
    ];

    // Cast images to an array
    protected $casts = [
        'images' => 'array',
    ];

    // Define the relationship with the User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
