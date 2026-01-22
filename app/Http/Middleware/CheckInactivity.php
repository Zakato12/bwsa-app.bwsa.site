<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckInactivity
{
    public function handle(Request $request, Closure $next)
    {
        // Skip check if the user is on the login page
        if ($request->is('/') || $request->is('login')) {
            return $next($request);
        }

        // Only check if our custom session exists
        if (session()->has('usr_id')) {
            $timeout = 60;
            $lastActivity = session('last_activity');

            if ($lastActivity && (time() - $lastActivity > $timeout)) {
                session()->flush();
                // Redirecting to the NAME 'login' is what prevents the Method error
                return redirect()->route('login')->with('error', 'Session expired due to inactivity.');
            }
            // Update timestamp
            session(['last_activity' => time()]);
        }

        return $next($request);
    }
}