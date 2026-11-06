<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiting();
    }

    /**
     * Named rate limiters for sensitive endpoints. Limits come from config/ratelimit.php.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('auth-token', function (Request $request) {
            return Limit::perMinute(config('ratelimit.auth_token'))->by('auth-token|' . $request->ip());
        });

        RateLimiter::for('session-token', function (Request $request) {
            return Limit::perMinute(config('ratelimit.session_token'))
                ->by('session-token|' . ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });

        RateLimiter::for('shop-buy', function (Request $request) {
            return Limit::perMinute(config('ratelimit.shop_buy'))
                ->by('shop-buy|' . ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });

        RateLimiter::for('vote-verify', function (Request $request) {
            return Limit::perMinute(config('ratelimit.vote_verify'))
                ->by('vote-verify|' . ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });
    }
}
