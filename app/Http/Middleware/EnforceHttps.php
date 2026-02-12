<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('app.force_https', false)) {
            return $next($request);
        }

        if ($request->isSecure()) {
            return $next($request);
        }

        if (app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        $target = 'https://' . $request->getHttpHost() . $request->getRequestUri();
        return redirect()->to($target, 301);
    }
}

