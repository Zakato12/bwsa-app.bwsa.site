<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionTimeout
{
    private int $timeoutSeconds = 300; // 5 minutes

    public function handle(Request $request, Closure $next): Response
    {
        $lastActivity = session('last_activity');
        if ($lastActivity && (time() - $lastActivity) > $this->timeoutSeconds) {
            $request->session()->flush();
            $request->session()->invalidate();
            $request->session()->regenerate();
            return redirect()->route('login')->with('error', 'Session expired. Please login again.');
        }

        session()->put('last_activity', time());

        return $next($request);
    }
}
