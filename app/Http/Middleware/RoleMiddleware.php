<?php

namespace App\Http\Middleware;

use App\Support\SecurityMonitor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $currentRole = session('usr_role');
        if (!$currentRole || !in_array($currentRole, $roles, true)) {
            SecurityMonitor::event('auth.role_denied', [
                'required_roles' => $roles,
                'current_role' => $currentRole,
            ]);
            SecurityMonitor::trackThreshold(
                'role_denied:' . $request->ip(),
                300,
                10,
                'alert.role_denied_spike',
                ['current_role' => $currentRole]
            );
            abort(403);
        }

        return $next($request);
    }
}
