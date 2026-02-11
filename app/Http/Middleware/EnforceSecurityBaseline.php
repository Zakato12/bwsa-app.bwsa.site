<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnforceSecurityBaseline
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('app.security_baseline_enabled', true)) {
            return $next($request);
        }

        if (!app()->environment('production')) {
            return $next($request);
        }

        $issues = [];

        if ((bool) config('app.debug', false) === true) {
            $issues[] = 'APP_DEBUG must be false in production';
        }

        $appKey = (string) config('app.key', '');
        if ($appKey === '' || $appKey === 'base64:') {
            $issues[] = 'APP_KEY is missing or invalid';
        }

        if ((bool) config('session.secure', false) === false) {
            $issues[] = 'SESSION_SECURE_COOKIE should be true in production';
        }

        if (!empty($issues)) {
            Log::critical('security.baseline_failed', [
                'issues' => $issues,
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
            ]);

            if ((bool) env('SECURITY_BASELINE_BLOCK_ON_FAIL', true) === true) {
                abort(503, 'Security baseline check failed.');
            }
        }

        return $next($request);
    }
}

