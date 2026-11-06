<?php

namespace Tests\Feature;

use App\Models\Stage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The Minecraft Usage Guidelines require a visible "not official" disclaimer on the website.
 */
class MinecraftDisclaimerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function locales(): array
    {
        return [
            'en' => ['en', 'NOT AN OFFICIAL MINECRAFT SERVICE. NOT APPROVED BY OR ASSOCIATED WITH MOJANG OR MICROSOFT.'],
            'fr' => ['fr', 'SERVICE MINECRAFT NON OFFICIEL. NON APPROUVÉ PAR MOJANG OU MICROSOFT ET SANS LIEN AVEC EUX.'],
        ];
    }

    #[DataProvider('locales')]
    public function test_footer_shows_the_disclaimer(string $locale, string $expected): void
    {
        Stage::factory()->create();

        $this->withUnencryptedCookie('locale', $locale)
            ->get('/')
            ->assertOk()
            ->assertSee($expected);
    }
}
