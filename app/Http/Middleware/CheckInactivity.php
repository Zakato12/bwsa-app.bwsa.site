<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInactivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $timeout = 60;
        $lastActivity = session('last_activity');

        if (session()->has('usr_id')) {
            if ($lastActivity && (time() - $lastActivity > $timeout)) {
                // Clear the session
                session()->flush();
                return redirect('/login')->with('error', 'You have been logged out due to inactivity.');
            }
            
            // Update the timestamp on every click/request
            session(['last_activity' => time()]);
        }

        return $next($request);
    }
}