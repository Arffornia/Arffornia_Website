<?php

namespace Tests\Feature;

use App\Rewards\CrateCatalog;
use Tests\TestCase;

class CrateCatalogTest extends TestCase
{
    public function test_odds_are_each_weight_over_the_total_in_percent(): void
    {
        $odds = CrateCatalog::odds([
            ['item' => 'minecraft:iron_ingot', 'weight' => 2, 'min' => 8, 'max' => 16],
            ['item' => 'minecraft:diamond', 'weight' => 1],
        ]);

        $this->assertSame([
            ['item' => 'minecraft:iron_ingot', 'min' => 8, 'max' => 16, 'rare' => false, 'odds' => 66.67],
            ['item' => 'minecraft:diamond', 'min' => 1, 'max' => 1, 'rare' => false, 'odds' => 33.33],
        ], $odds);
    }

    public function test_the_shipped_streak_crate_matches_the_mods_streak_loot_table(): void
    {
        $crates = collect(app(CrateCatalog::class)->all())->keyBy('id');

        $odds = array_column($crates['arffornia:streak']['loot'], 'odds', 'item');

        $this->assertSame([
            'minecraft:iron_ingot' => 40.0,
            'minecraft:gold_ingot' => 25.0,
            'minecraft:redstone' => 20.0,
            'minecraft:diamond' => 12.0,
            'minecraft:netherite_scrap' => 3.0,
        ], $odds);
    }

    public function test_every_shipped_crate_has_a_name_in_both_locales(): void
    {
        foreach (app(CrateCatalog::class)->all() as $crate) {
            foreach (['en', 'fr'] as $locale) {
                $this->assertTrue(
                    \Illuminate\Support\Facades\Lang::has('crates.' . $crate['translate'], $locale, false),
                    "{$crate['id']} has no {$locale} name"
                );
            }
        }
    }
}
