<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRegion extends Model
{
    protected $fillable = [
        'name',
        'city',
        'state',
        'country',
        'center_lat',
        'center_lng',
        'radius_km',
        'is_enabled',
        'notes',
    ];

    protected $casts = [
        'center_lat' => 'decimal:7',
        'center_lng' => 'decimal:7',
        'is_enabled' => 'boolean',
        'radius_km' => 'integer',
    ];
}
