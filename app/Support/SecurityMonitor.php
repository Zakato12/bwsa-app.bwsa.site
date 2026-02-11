<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecurityMonitor
{
    public static function event(string $event, array $context = [], string $level = 'warning'): void
    {
        $payload = array_merge([
            'event' => $event,
            'user_id' => session('usr_id'),
            'role' => session('usr_role'),
            'ip' => request()?->ip(),
            'route' => request()?->path(),
        ], $context);

        Log::channel('security')->log($level, $event, $payload);
    }

    public static function trackThreshold(
        string $key,
        int $windowSeconds,
        int $threshold,
        string $alertEvent,
        array $context = []
    ): void {
        $cacheKey = 'secmon:' . $key;
        $count = (int) Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $count, now()->addSeconds($windowSeconds));

        if ($count === $threshold) {
            self::event($alertEvent, array_merge($context, [
                'count' => $count,
                'window_seconds' => $windowSeconds,
            ]), 'critical');
        }
    }
}

