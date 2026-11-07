<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
            });
        }
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('api', [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \App\Http\Middleware\SetApiLocale::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // Website locale (EN/FR). The `locale` cookie only holds "en" or "fr" (validated on read),
        // so it is not encrypted.
        $middleware->encryptCookies(except: ['locale']);
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->alias(aliases: [
            'anyRole' => \App\Http\Middleware\CheckAnyRole::class,
            'allRole' => \App\Http\Middleware\CheckAllRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Route-model binding misses: Laravel's default text names the model class and ignores the API locale.
        // The `message` key is kept so clients see the same JSON shape.
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') && $e->getPrevious() instanceof ModelNotFoundException) {
                return response()->json(['message' => __('api.not_found')], 404);
            }
        });
    })->create();
