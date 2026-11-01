<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * API locale negotiation (App\Http\Middleware\SetApiLocale) and API message translations.
 * Order: ?locale=, then the `locale` cookie, then Accept-Language, then `en`.
 */
class ApiLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_to_english_with_the_former_text(): void
    {
        $this->getJson('/api/profile/nobody')
            ->assertNotFound()
            ->assertHeader('Content-Language', 'en')
            ->assertExactJson(['error' => 'player name not found.']);
    }

    public function test_follows_accept_language(): void
    {
        $this->withHeader('Accept-Language', 'fr-FR,fr;q=0.9,en;q=0.8')
            ->getJson('/api/profile/nobody')
            ->assertHeader('Content-Language', 'fr')
            ->assertExactJson(['error' => 'nom de joueur introuvable.']);
    }

    public function test_unsupported_accept_language_falls_back_to_english(): void
    {
        $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')
            ->getJson('/api/profile/nobody')
            ->assertHeader('Content-Language', 'en');
    }

    public function test_query_parameter_wins_over_header_and_cookie(): void
    {
        $this->withHeader('Accept-Language', 'en')
            ->withCredentials()
            ->withUnencryptedCookie('locale', 'en')
            ->getJson('/api/profile/nobody?locale=fr')
            ->assertHeader('Content-Language', 'fr');

        $this->withHeader('Accept-Language', 'fr')
            ->getJson('/api/profile/nobody?locale=EN')
            ->assertHeader('Content-Language', 'en');
    }

    public function test_invalid_query_parameter_is_ignored(): void
    {
        $this->withHeader('Accept-Language', 'fr')
            ->getJson('/api/profile/nobody?locale=xx')
            ->assertHeader('Content-Language', 'fr');
    }

    public function test_website_cookie_is_honoured(): void
    {
        // JSON test requests only carry cookies with credentials, like a same-origin fetch() from a page.
        $this->withCredentials()
            ->withUnencryptedCookie('locale', 'fr')
            ->getJson('/api/profile/nobody')
            ->assertHeader('Content-Language', 'fr');
    }

    public function test_response_varies_on_accept_language(): void
    {
        $vary = $this->getJson('/api/profile/nobody')->headers->get('Vary');

        $this->assertStringContainsString('Accept-Language', (string) $vary);
    }

    public function test_validation_errors_follow_the_locale(): void
    {
        $en = $this->postJson('/api/auth/token/svc', [])->assertUnprocessable()->json('errors.client_id.0');
        $fr = $this->withHeader('Accept-Language', 'fr')
            ->postJson('/api/auth/token/svc', [])->assertUnprocessable()->json('errors.client_id.0');

        $this->assertNotSame($en, $fr);
    }

    public function test_every_api_key_used_in_app_exists_in_both_locales(): void
    {
        $root = dirname(__DIR__, 2) . '/app';
        $keys = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root)) as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.php')) {
                preg_match_all("/__\\(\\s*'(api\\.[a-z_.]+)'/", file_get_contents($file->getPathname()), $m);
                array_push($keys, ...$m[1]);
            }
        }
        $keys = array_values(array_unique($keys));

        $this->assertGreaterThan(30, count($keys), 'expected the API messages to use api.* keys');
        foreach ($keys as $key) {
            foreach (['en', 'fr'] as $locale) {
                $this->assertTrue(Lang::hasForLocale($key, $locale), "$key missing in lang/$locale/api.php");
                $this->assertIsString(__($key, [], $locale), "$key must be a string, not a group");
            }
        }
    }
}
