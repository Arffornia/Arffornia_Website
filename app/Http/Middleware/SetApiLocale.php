<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the locale of an API response (messages, validation errors, and translated content once #100 lands):
 * `?locale=en|fr`, then the `locale` cookie (website pages calling the API), then Accept-Language, then
 * config('app.api_default_locale') = `en`. The chosen locale is echoed in Content-Language.
 *
 * See the decision record "Player-visible text in data and APIs" (Arffornia/docs, ADR 0009).
 */
class SetApiLocale
{
    public const QUERY = 'locale';

    public function handle(Request $request, Closure $next): Response
    {
        $locale = self::resolve($request);
        App::setLocale($locale);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('Content-Language', $locale);
        $response->setVary('Accept-Language', false);

        return $response;
    }

    public static function resolve(Request $request): string
    {
        $query = $request->query(self::QUERY);
        if (is_string($query) && in_array(strtolower($query), SetLocale::supported(), true)) {
            return strtolower($query);
        }

        return SetLocale::negotiate($request, config('app.api_default_locale', 'en'));
    }
}
