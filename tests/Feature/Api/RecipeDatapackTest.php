<?php

namespace Tests\Feature\Api;

use App\Models\MilestoneUnlock;
use App\Models\Recipe;
use App\Services\RecipeDatapackService;
use ZipArchive;

/**
 * Contract for GET /api/progression/datapack, downloaded by the mod (Arffornia_Mods RecipePackUpdater).
 */
class RecipeDatapackTest extends ApiTestCase
{
    private function unlock(array $bans): MilestoneUnlock
    {
        return MilestoneUnlock::factory()->create([
            'milestone_id' => $this->milestone()->id,
            'recipes_to_ban' => $bans,
        ]);
    }

    /** @return array<string, array<mixed>> */
    private function entries(string $zipBytes): array
    {
        $path = tempnam(sys_get_temp_dir(), 'pack');
        file_put_contents($path, $zipBytes);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $entries[$name] = json_decode($zip->getFromIndex($i), true);
        }
        $zip->close();
        unlink($path);

        return $entries;
    }

    public function test_datapack_contains_machine_recipes_and_ban_overrides(): void
    {
        $unlock = $this->unlock(['mekanism:steel_casing', 'mekanism:steel_casing_alt']);
        Recipe::factory()->create([
            'type' => 'arffornia:mekanism_steel_casing_recipe',
            'milestone_unlock_id' => $unlock->id,
            'ingredients' => [
                ['item' => 'minecraft:iron_ingot', 'count' => 1],
                ['item' => 'minecraft:iron_ingot', 'count' => 1],
                ['tag' => 'c:glass_blocks', 'count' => 2],
                null,
            ],
            'result' => [['item' => 'mekanism:steel_casing', 'count' => 2]],
            'energy' => 4000,
            'time' => 100,
        ]);

        $response = $this->get('/api/progression/datapack');

        $response->assertOk()->assertHeader('Content-Type', 'application/zip');
        $entries = $this->entries($response->getContent());
        $this->assertSame(48, $entries['pack.mcmeta']['pack']['pack_format']);
        $this->assertSame(['neoforge:conditions' => [['type' => 'neoforge:false']]], $entries['data/mekanism/recipe/steel_casing_alt.json']);
        $this->assertSame([
            'type' => 'arffornia:machine',
            'machine' => 'arffornia:crafter',
            'stage' => 'arffornia:milestone/' . $unlock->milestone_id,
            'ingredients' => [
                ['ingredient' => ['item' => 'minecraft:iron_ingot'], 'count' => 2],
                ['ingredient' => ['tag' => 'c:glass_blocks'], 'count' => 2],
            ],
            'results' => [['id' => 'mekanism:steel_casing', 'count' => 2]],
            'energy' => 4000,
            'time' => 100,
        ], $entries['data/arffornia/recipe/mekanism_steel_casing_recipe.json']);
    }

    public function test_datapack_bytes_do_not_depend_on_time_or_row_order(): void
    {
        $service = app(RecipeDatapackService::class);
        foreach (['a', 'b', 'c'] as $suffix) {
            Recipe::factory()->create(['type' => "arffornia:recipe_$suffix", 'milestone_unlock_id' => $this->unlock(["mekanism:item_$suffix"])->id]);
        }
        $first = $service->build();
        sleep(2);
        $second = $service->build();

        $this->assertSame(hash('sha256', $first), hash('sha256', $second));

        Recipe::query()->delete();
        MilestoneUnlock::query()->delete();
        foreach (['c', 'b', 'a'] as $suffix) {
            Recipe::factory()->create(['type' => "arffornia:recipe_$suffix", 'milestone_unlock_id' => $this->unlock(["mekanism:item_$suffix"])->id]);
        }
        $reversed = $service->files();

        $this->assertSame(['data/arffornia/recipe/recipe_a.json', 'data/arffornia/recipe/recipe_b.json', 'data/arffornia/recipe/recipe_c.json',
            'data/mekanism/recipe/item_a.json', 'data/mekanism/recipe/item_b.json', 'data/mekanism/recipe/item_c.json'], array_keys($reversed));
    }

    public function test_etag_is_the_sha256_of_the_body_and_answers_not_modified(): void
    {
        Recipe::factory()->create(['milestone_unlock_id' => $this->unlock(['mekanism:item'])->id]);

        $response = $this->get('/api/progression/datapack');
        $etag = $response->headers->get('ETag');

        $this->assertSame('"' . hash('sha256', $response->getContent()) . '"', $etag);
        $this->get('/api/progression/datapack', ['If-None-Match' => $etag])->assertStatus(304);
    }

    public function test_etag_changes_when_a_recipe_or_an_unlock_changes(): void
    {
        $unlock = $this->unlock(['mekanism:item']);
        $recipe = Recipe::factory()->create(['milestone_unlock_id' => $unlock->id]);
        $initial = $this->get('/api/progression/datapack')->headers->get('ETag');

        $this->travel(2)->seconds();
        $recipe->update(['ingredients' => [['item' => 'minecraft:gold_ingot', 'count' => 3]]]);
        $afterRecipeEdit = $this->get('/api/progression/datapack')->headers->get('ETag');

        $this->travel(2)->seconds();
        $this->unlock(['mekanism:other'])->delete();
        $extra = $this->unlock(['minecraft:stick']);
        $afterUnlockChange = $this->get('/api/progression/datapack')->headers->get('ETag');
        $extra->delete();
        $afterUnlockDelete = $this->get('/api/progression/datapack')->headers->get('ETag');

        $this->assertNotSame($initial, $afterRecipeEdit);
        $this->assertNotSame($afterRecipeEdit, $afterUnlockChange);
        $this->assertNotSame($afterUnlockChange, $afterUnlockDelete);
    }

    public function test_datapack_skips_ids_the_mod_would_reject(): void
    {
        $this->unlock(['minecraft:../../evil', '..:stick', 'a..b:stick', 'minecraft:/x', 'minecraft:./x', 'minecraft:a//b', '.:x', 'Not An Id', 'minecraft:stick']);

        $entries = $this->entries($this->get('/api/progression/datapack')->getContent());

        $this->assertEqualsCanonicalizing(['pack.mcmeta', 'data/minecraft/recipe/stick.json'], array_keys($entries));
    }

    public function test_recipe_with_an_invalid_ingredient_is_skipped_rather_than_published_cheaper(): void
    {
        $unlock = $this->unlock([]);
        Recipe::factory()->create([
            'type' => 'arffornia:broken',
            'milestone_unlock_id' => $unlock->id,
            'ingredients' => [['item' => 'minecraft:iron_ingot', 'count' => 1], ['item' => 'Minecraft:Diamond', 'count' => 1]],
        ]);

        $entries = $this->entries($this->get('/api/progression/datapack')->getContent());

        $this->assertArrayNotHasKey('data/arffornia/recipe/broken.json', $entries);
    }

    public function test_recipe_with_a_result_larger_than_a_stack_is_skipped(): void
    {
        Recipe::factory()->create([
            'type' => 'arffornia:too_many',
            'milestone_unlock_id' => $this->unlock([])->id,
            'result' => [['item' => 'minecraft:stone', 'count' => 100]],
        ]);

        $entries = $this->entries($this->get('/api/progression/datapack')->getContent());

        $this->assertArrayNotHasKey('data/arffornia/recipe/too_many.json', $entries);
    }

    public function test_datapack_is_public(): void
    {
        $this->get('/api/progression/datapack')->assertOk();
    }
}
