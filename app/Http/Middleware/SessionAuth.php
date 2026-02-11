<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('usr_id')) {
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        $currentUaHash = hash('sha256', (string) $request->userAgent());
        $sessionUaHash = (string) session('auth_ua_hash');
        if ($sessionUaHash !== '' && !hash_equals($sessionUaHash, $currentUaHash)) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Session invalidated. Please login again.');
        }

        if (config('session.bind_ip', false)) {
            $sessionIp = (string) session('auth_ip');
            $currentIp = (string) $request->ip();
            if ($sessionIp !== '' && $currentIp !== '' && $sessionIp !== $currentIp) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->with('error', 'Session invalidated. Please login again.');
            }
        }

        return $next($request);
    }
}
