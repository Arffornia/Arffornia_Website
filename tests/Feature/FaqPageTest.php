<?php

namespace Tests\Feature;

use App\Models\Stage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FaqPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function locales(): array
    {
        return [
            'en' => ['en', 'Can I play on a Mac?', 'Open Anyway'],
            'fr' => ['fr', 'Peut-on jouer sur Mac ?', 'Ouvrir quand même'],
        ];
    }

    #[DataProvider('locales')]
    public function test_mac_entry_renders_in_each_language(string $locale, string $question, string $gatekeeperButton): void
    {
        $this->withUnencryptedCookie('locale', $locale)
            ->get('/faq')
            ->assertOk()
            ->assertSee('id="mac"', false)
            ->assertSee($question)
            ->assertSee($gatekeeperButton);
    }

    public function test_footer_links_to_the_faq(): void
    {
        Stage::factory()->create();

        $this->get('/')->assertOk()->assertSee('href="' . route('faq') . '"', false);
    }
}
