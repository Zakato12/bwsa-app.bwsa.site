<?php

namespace App\Http\Middleware;

use App\Support\SecurityMonitor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('usr_id')) {
            SecurityMonitor::event('auth.missing_session');
            return redirect()->route('login')->with('error', 'Please login first.');
        }

        $currentUaHash = hash('sha256', (string) $request->userAgent());
        $sessionUaHash = (string) session('auth_ua_hash');
        if ($sessionUaHash !== '' && !hash_equals($sessionUaHash, $currentUaHash)) {
            SecurityMonitor::event('auth.session_ua_mismatch');
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->with('error', 'Session invalidated. Please login again.');
        }

        if (config('session.bind_ip', false)) {
            $sessionIp = (string) session('auth_ip');
            $currentIp = (string) $request->ip();
            if ($sessionIp !== '' && $currentIp !== '' && $sessionIp !== $currentIp) {
                SecurityMonitor::event('auth.session_ip_mismatch', [
                    'session_ip' => $sessionIp,
                    'current_ip' => $currentIp,
                ]);
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->with('error', 'Session invalidated. Please login again.');
            }
        }

        return $next($request);
    }
}
