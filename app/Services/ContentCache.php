<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Version-keyed cache for public API payloads. Any admin change bumps the
 * version, so the frontend sees updates immediately with no stale entries
 * (works on every cache driver, no tags required).
 */
class ContentCache
{
    private const VERSION_KEY = 'content_version';

    public static function version(): int
    {
        return (int) Cache::rememberForever(self::VERSION_KEY, fn () => 1);
    }

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    public static function remember(string $key, Closure $callback, int $ttl = 3600): mixed
    {
        return Cache::remember('api:v'.self::version().':'.$key, $ttl, $callback);
    }
}
