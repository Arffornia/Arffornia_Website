<?php

namespace Tests\Feature;

use App\Models\Stage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Legal notice, privacy policy and terms of use (docs spec 012): both languages, linked
 * from the footer of every page.
 */
class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function pages(): array
    {
        return [
            'notice en' => ['/legal', 'en', 'Legal notice'],
            'notice fr' => ['/legal', 'fr', 'Mentions légales'],
            'privacy en' => ['/privacy', 'en', 'Privacy policy'],
            'privacy fr' => ['/privacy', 'fr', 'Politique de confidentialité'],
            'terms en' => ['/terms', 'en', 'Terms of use'],
            'terms fr' => ['/terms', 'fr', 'Conditions générales d&#039;utilisation'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders_in_each_language(string $path, string $locale, string $title): void
    {
        $this->withUnencryptedCookie('locale', $locale)
            ->get($path)
            ->assertOk()
            ->assertSee($title, false)
            ->assertSee('arffornia@gmail.com');
    }

    public function test_legal_notice_names_the_publisher_and_the_minecraft_disclaimer(): void
    {
        $this->withUnencryptedCookie('locale', 'en')
            ->get('/legal')
            ->assertOk()
            ->assertSee('TheGostsniperfr')
            ->assertSee('Minecraft is a trademark of Mojang AB.');
    }

    public function test_terms_link_to_the_server_rules(): void
    {
        $this->get('/terms')->assertOk()->assertSee(url('/reglement'), false);
    }

    #[DataProvider('locales')]
    public function test_footer_links_to_every_legal_page(string $locale): void
    {
        Stage::factory()->create();

        $response = $this->withUnencryptedCookie('locale', $locale)->get('/')->assertOk();
        foreach (['legal.notice', 'legal.privacy', 'legal.terms'] as $route) {
            $response->assertSee('href="' . route($route) . '"', false);
        }
    }

    /**
     * @return array<string, array{string}>
     */
    public static function locales(): array
    {
        return ['en' => ['en'], 'fr' => ['fr']];
    }
}
