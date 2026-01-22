<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Http\Request;
use Closure;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    protected function handleTokenMismatch(Request $request, Closure $next)
{
    if ($request->session()->has('_token')) {
        // Optionally regenerate token
        $request->session()->regenerateToken();
    }
    return redirect()->back()->withErrors(['csrf' => 'Session expired. Please try again.']);
}
}
