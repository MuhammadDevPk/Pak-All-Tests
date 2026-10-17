<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class PlatformSetting extends Model
{
    protected $guarded = [];

    /**
     * Retrieve a setting value by key with caching.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("platform_setting:{$key}", now()->addHour(), function () use ($key, $default) {
            $setting = static::query()->where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    /**
     * Persist or update a setting and bust its cache.
     */
    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value]
        );

        Cache::forget("platform_setting:{$key}");
    }

    /**
     * Determine if the platform-wide master free toggle is enabled.
     * Defaults to true during user acquisition/growth phase in production/dev,
     * while defaulting to false in testing to allow subscription gating tests to execute cleanly.
     */
    public static function isAllDomainsFree(): bool
    {
        $default = app()->runningUnitTests() ? '0' : '1';
        $value = static::get('all_domains_free', $default);

        return in_array($value, [true, 1, '1', 'true'], true);
    }

    /**
     * Determine if unauthenticated visitors should automatically be signed in as ephemeral guests.
     * Defaults to true in production/local for instant frictionless student onboarding,
     * while defaulting to false during unit testing so unauthenticated route guards remain testable.
     */
    public static function isAutoGuestLoginEnabled(): bool
    {
        $default = app()->runningUnitTests() ? '0' : '1';
        $value = static::get('auto_guest_login', $default);

        return in_array($value, [true, 1, '1', 'true'], true);
    }
}
