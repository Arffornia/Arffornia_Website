<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the website locale: explicit choice (cookie), then Accept-Language, then the configured default.
 * Registered on the `web` group; API routes use SetApiLocale (same order, plus `?locale=`, default `en`).
 */
class SetLocale
{
    public const COOKIE = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale(self::resolve($request));

        return $next($request);
    }

    public static function resolve(Request $request): string
    {
        return self::negotiate($request, config('app.web_default_locale', 'fr'));
    }

    /**
     * Cookie, then Accept-Language, then $default (or the fallback locale if $default is not supported).
     */
    public static function negotiate(Request $request, ?string $default): string
    {
        $supported = self::supported();

        $chosen = $request->cookie(self::COOKIE);
        if (is_string($chosen) && in_array($chosen, $supported, true)) {
            return $chosen;
        }

        foreach ($request->getLanguages() as $language) {
            // "fr_CA" / "en_US" -> "fr" / "en"
            $primary = strtolower(explode('_', $language)[0]);
            if (in_array($primary, $supported, true)) {
                return $primary;
            }
        }

        return in_array($default, $supported, true) ? $default : config('app.fallback_locale', 'en');
    }

    /**
     * @return array<int, string>
     */
    public static function supported(): array
    {
        return config('app.supported_locales', ['en', 'fr']);
    }
}
