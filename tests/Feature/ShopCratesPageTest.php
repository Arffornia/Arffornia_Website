<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCratesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_shop_lists_each_crate_with_its_contents_and_odds(): void
    {
        $response = $this->withUnencryptedCookie('locale', 'en')->get('/shop');

        $response->assertOk()
            ->assertSee('Streak crate')
            ->assertSee('Vote crate')
            ->assertSee('Legendary crate')
            ->assertSee('Netherite Scrap')
            ->assertSee('8–16')
            ->assertSee('40 %')
            ->assertSee('/icons/36/minecraft/diamond.png', false);
    }

    public function test_the_legendary_crate_lists_its_contents(): void
    {
        $this->withUnencryptedCookie('locale', 'fr')->get('/shop')
            ->assertOk()
            ->assertSee('Coffre légendaire')
            ->assertSee('Elytra')
            ->assertDontSee('Contenu bientôt disponible.');
    }
}
