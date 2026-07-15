<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'billing_cycle',
        'price',
        'discount_percent',
        'is_promotional',
        'is_active',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'is_promotional' => 'boolean',
        'is_active' => 'boolean',
    ];
}
