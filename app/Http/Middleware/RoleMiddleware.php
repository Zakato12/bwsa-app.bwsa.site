<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $currentRole = session('usr_role');
        if (!$currentRole || !in_array($currentRole, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
