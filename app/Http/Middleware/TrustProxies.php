<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies;

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = Request::HEADER_X_FORWARDED_AWS_ELB;

    public function __construct()
    {
        $trustedProxies = trim((string) env('TRUSTED_PROXIES', ''));
        $this->proxies = $trustedProxies === ''
            ? null
            : array_map('trim', explode(',', $trustedProxies));

        $headerMode = strtoupper(trim((string) env('TRUSTED_PROXY_HEADERS', 'ALL')));
        $this->headers = match ($headerMode) {
            'AWS_ELB' => Request::HEADER_X_FORWARDED_AWS_ELB,
            'FORWARDED' => Request::HEADER_FORWARDED,
            default => Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        };
    }
}
