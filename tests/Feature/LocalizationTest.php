<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $missingKeys = [];

    protected function setUp(): void
    {
        parent::setUp();

        Lang::handleMissingKeysUsing(function (string $key, array $replace, ?string $locale) {
            $this->missingKeys[] = "$locale:$key";

            return $key;
        });

        Stage::factory()->create();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pagesAndLocales(): array
    {
        $pages = ['/', '/news', '/reglement', '/shop', '/vote', '/stages'];
        $cases = [];
        foreach ($pages as $page) {
            foreach (['en', 'fr'] as $locale) {
                $cases["$page ($locale)"] = [$page, $locale];
            }
        }

        return $cases;
    }

    #[DataProvider('pagesAndLocales')]
    public function test_page_renders_in_locale_without_missing_keys(string $page, string $locale): void
    {
        User::factory(3)->create();

        $response = $this->withUnencryptedCookie('locale', $locale)->get($page);

        $response->assertOk();
        $response->assertSee('<html lang="' . $locale . '">', false);
        $this->assertSame([], $this->missingKeys, 'Missing translation keys');
        // A raw key in the output (e.g. "home.slogan.intro") means a missing translation.
        $this->assertDoesNotMatchRegularExpression(
            '/>\s*(common|nav|home|news|rules|profile|vote|shop|stages|admin|widgets|messages)\.[a-z_]+(\.[a-z_]+)*\s*</',
            $response->getContent()
        );
    }

    public function test_profile_page_renders_in_both_locales(): void
    {
        $user = User::factory()->create();

        foreach (['en', 'fr'] as $locale) {
            $this->withUnencryptedCookie('locale', $locale)
                ->get('/profile/' . $user->name)
                ->assertOk()
                ->assertSee(__('profile.username', [], $locale));
        }
        $this->assertSame([], $this->missingKeys);
    }

    public function test_default_locale_is_french_without_preference(): void
    {
        // Symfony test requests send "Accept-Language: en-us" by default: clear it.
        $this->withHeader('Accept-Language', '')->get('/reglement')->assertOk()->assertSee('<html lang="fr">', false)->assertSee('Règlement');
    }

    public function test_accept_language_is_used_when_no_choice_was_made(): void
    {
        $this->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('/reglement')
            ->assertSee('<html lang="en">', false)
            ->assertSee('Server rules');

        $this->withHeader('Accept-Language', 'de-DE,fr;q=0.8')
            ->get('/reglement')
            ->assertSee('<html lang="fr">', false);
    }

    public function test_cookie_choice_wins_over_accept_language(): void
    {
        $this->withHeader('Accept-Language', 'en')
            ->withUnencryptedCookie('locale', 'fr')
            ->get('/reglement')
            ->assertSee('<html lang="fr">', false);
    }

    public function test_unsupported_cookie_value_is_ignored(): void
    {
        $this->withHeader('Accept-Language', 'en')
            ->withUnencryptedCookie('locale', '../../etc')
            ->get('/reglement')
            ->assertOk()
            ->assertSee('<html lang="en">', false);
    }

    public function test_switcher_stores_the_choice_in_a_cookie(): void
    {
        $this->from('/reglement')
            ->post('/locale', ['locale' => 'en'])
            ->assertRedirect('/reglement')
            ->assertPlainCookie('locale', 'en');
    }

    public function test_switcher_rejects_unknown_locales(): void
    {
        $this->from('/reglement')
            ->post('/locale', ['locale' => 'de'])
            ->assertRedirect('/reglement')
            ->assertCookieMissing('locale');
    }

    public function test_switcher_is_shown_in_the_layout(): void
    {
        $this->get('/')
            ->assertSee('action="' . route('locale.switch') . '"', false)
            ->assertSee('name="locale" value="en"', false)
            ->assertSee('name="locale" value="fr"', false);
    }

    public function test_api_routes_default_to_english_without_a_locale_hint(): void
    {
        // The website's French default does not leak into the API (see SetApiLocale and ApiLocaleTest).
        $this->getJson('/api/profile/nobody');

        $this->assertSame('en', app()->getLocale());
    }
}
