<?php

/*
|--------------------------------------------------------------------------
| Rate limits
|--------------------------------------------------------------------------
|
| Requests per minute for the named limiters registered in
| App\Providers\AppServiceProvider. Authenticated requests are keyed by
| user id, anonymous ones by client IP (see TRUSTED_PROXIES behind a gateway).
|
*/

return [
    // POST /api/auth/token/ms and /api/auth/token/svc (anonymous, per IP)
    'auth_token' => (int) env('RATE_LIMIT_AUTH_TOKEN', 10),

    // POST /api/auth/token/session (per user, called on shop and stages page loads)
    'session_token' => (int) env('RATE_LIMIT_SESSION_TOKEN', 30),

    // POST /shop/buy/{item} (per user)
    'shop_buy' => (int) env('RATE_LIMIT_SHOP_BUY', 10),

    // POST /vote/verify (per user)
    'vote_verify' => (int) env('RATE_LIMIT_VOTE_VERIFY', 6),
];
