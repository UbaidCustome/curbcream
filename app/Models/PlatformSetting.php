<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, $default = null)
    {
        $settings = Cache::remember('platform_settings', 60, function () {
            return static::query()->pluck('value', 'key')->toArray();
        });

        return $settings[$key] ?? $default;
    }

    public static function setValue(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('platform_settings');
    }

    public static function many(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::setValue($key, $value);
        }
    }

    /**
     * Max matching radius in kilometers from admin Location settings.
     * Falls back to $defaultKm when unset/invalid. Converts miles → km when needed.
     */
    public static function maxServiceDistanceKm(float $defaultKm = 5.0): float
    {
        $raw = static::getValue('max_service_distance_km');

        if ($raw === null || $raw === '' || !is_numeric($raw)) {
            return $defaultKm;
        }

        $distance = (float) $raw;

        if ($distance <= 0) {
            return $defaultKm;
        }

        if (static::getValue('distance_unit', 'km') === 'miles') {
            $distance *= 1.60934;
        }

        return round($distance, 2);
    }
}
