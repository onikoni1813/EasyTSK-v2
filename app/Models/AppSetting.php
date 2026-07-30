<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value'];

    protected static array $localCache = [];

    /**
     * Fetch all settings in a single cached array query to eliminate N+1 DB & Cache overhead.
     */
    public static function getAllCached(): array
    {
        if (!empty(static::$localCache)) {
            return static::$localCache;
        }

        static::$localCache = Cache::remember('all_app_settings', 3600, function () {
            return static::pluck('value', 'key')->toArray();
        });

        return static::$localCache;
    }

    public static function getByKey(string $key, $default = null)
    {
        $all = static::getAllCached();
        return $all[$key] ?? $default;
    }

    public static function setByKey(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('all_app_settings');
        Cache::forget("app_setting_{$key}");
        static::$localCache = [];
    }

    /**
     * Reward multiplier currently in effect for task/offerwall coin rewards.
     * Returns 2.0 during Happy Hour, 1.0 otherwise.
     */
    public static function rewardMultiplier(): float
    {
        return static::getByKey('happy_hour', 'false') === 'true' ? 2.0 : 1.0;
    }

    /**
     * Number of hours offerwall rewards remain pending before release.
     */
    public static function offerwallPendingHours(): int
    {
        return (int) static::getByKey('offerwall_pending_hours', '24');
    }
}

