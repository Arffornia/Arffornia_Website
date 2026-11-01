<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | Reverse proxies (e.g. the Kubernetes gateway) whose X-Forwarded-* headers
    | are trusted, so request()->ip() returns the real client address. This is
    | read by Laravel's TrustProxies middleware.
    |
    | Comma-separated IP addresses or CIDR ranges, e.g. "10.42.0.0/16,10.43.0.0/16".
    | Unset (default) trusts no proxy. "REMOTE_ADDR" trusts the direct caller and
    | "*" trusts any caller: only use those when nothing else can reach the app.
    |
    | Note: nginx in the same pod talks FastCGI to php-fpm, so REMOTE_ADDR is the
    | address of the proxy in front of nginx (the gateway), not 127.0.0.1.
    |
    */

    'proxies' => env('TRUSTED_PROXIES'),

];
