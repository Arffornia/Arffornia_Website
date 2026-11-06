<?php

namespace Tests\Feature\Api;

use App\Models\LauncherImage;
use App\Models\MilestoneUnlock;
use App\Models\ShopItem;
use App\Models\Vote;

/**
 * Public (unauthenticated) endpoints used by the launcher and the mod.
 */
class PublicEndpointsContractTest extends ApiTestCase
{
    public function test_best_player_point_returns_name_uuid_value_ordered_by_points(): void
    {
        $low = $this->player(['progress_point' => 10]);
        $high = $this->player(['progress_point' => 500]);
        $this->player(['progress_point' => 1]);

        $this->getJson('/api/best_player_point/2')
            ->assertOk()
            ->assertExactJson([
                ['name' => $high->name, 'uuid' => $high->uuid, 'value' => 500],
                ['name' => $low->name, 'uuid' => $low->uuid, 'value' => 10],
            ]);
    }

    public function test_best_player_vote_returns_name_uuid_value(): void
    {
        $voter = $this->player();
        Vote::create(['user_id' => $voter->id, 'site' => 'smc', 'ip_address' => '203.0.113.1']);
        Vote::create(['user_id' => $voter->id, 'site' => 'smc', 'ip_address' => '203.0.113.1']);

        $this->getJson('/api/best_player_vote/5')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', $voter->name)
            ->assertJsonPath('0.uuid', $voter->uuid)
            ->assertJsonPath('0.value', 2);

        $this->getJson('/api/best_player_vote/all-time/5')
            ->assertOk()
            ->assertJsonStructure([['name', 'uuid', 'value']]);
    }

    public function test_profile_by_name_and_uuid_share_the_same_shape(): void
    {
        $player = $this->player(['money' => 42, 'grade' => 'citizen', 'day_streak' => 3]);
        $shape = ['name', 'uuid', 'money', 'grade', 'day_streak', 'vote_count', 'active_progression_id'];

        $byName = $this->getJson("/api/profile/{$player->name}")
            ->assertOk()
            ->assertJsonStructure($shape)
            ->assertJson([
                'name' => $player->name,
                'uuid' => $player->uuid,
                'grade' => 'citizen',
                'day_streak' => 3,
                'vote_count' => 0,
                'active_progression_id' => $player->active_progression_id,
            ]);
        $this->assertEquals(42, $byName->json('money'));

        // The mod reads active_progression_id (PlayerData DTO)
        $this->getJson("/api/profile/uuid/{$player->uuid}")
            ->assertOk()
            ->assertJsonStructure($shape)
            ->assertJsonPath('active_progression_id', $player->active_progression_id);
    }

    public function test_unknown_profile_returns_404_with_an_error_key(): void
    {
        // The launcher displays response.data.error
        $this->getJson('/api/profile/NobodyHere')->assertNotFound()->assertJsonStructure(['error']);
        $this->getJson('/api/profile/uuid/00000000000000000000000000000000')->assertNotFound()->assertJsonStructure(['error']);
    }

    public function test_player_stages_returns_the_graph_data_shape_used_by_the_mod(): void
    {
        $player = $this->player();
        $milestone = $this->milestone();

        $this->getJson("/api/stages/player/get/{$player->uuid}")
            ->assertOk()
            ->assertJsonStructure([
                'graphs',
                'milestones' => [['id', 'graph_id', 'icon_type', 'x', 'y', 'stage_number', 'first_unlock_item_id']],
                'milestone_closure',
                'playerProgress' => ['completed_milestones', 'current_target_id', 'max_stage_number'],
            ])
            ->assertJsonPath('milestones.0.id', $milestone->id)
            ->assertJsonPath('playerProgress.max_stage_number', $this->stage->number);
    }

    public function test_player_stages_for_unknown_player_is_404(): void
    {
        $this->getJson('/api/stages/player/get/00000000000000000000000000000000')->assertNotFound();
    }

    public function test_milestone_details_shape(): void
    {
        $milestone = $this->milestone();
        MilestoneUnlock::create([
            'milestone_id' => $milestone->id,
            'item_id' => 'minecraft:furnace',
            'display_name' => 'Furnace',
            'recipes_to_ban' => ['minecraft:furnace'],
            'shop_price' => 5,
        ]);
        $milestone->requirements()->create(['item_id' => 'minecraft:cobblestone', 'display_name' => 'Cobblestone', 'amount' => 8]);

        $this->getJson("/api/milestone/get/{$milestone->id}")
            ->assertOk()
            ->assertJsonStructure([
                'id', 'name', 'description', 'stage_id', 'stage_number', 'reward_progress_points',
                'unlocks' => [['item_id', 'display_name', 'shop_price']],
                'requirements' => [['item_id', 'display_name', 'amount']],
            ])
            ->assertJsonPath('stage_number', $this->stage->number)
            ->assertJsonPath('unlocks.0.item_id', 'minecraft:furnace')
            ->assertJsonPath('requirements.0.amount', 8);
    }

    public function test_progression_config_lists_banned_recipes(): void
    {
        $milestone = $this->milestone();
        MilestoneUnlock::create([
            'milestone_id' => $milestone->id,
            'item_id' => 'minecraft:furnace',
            'recipes_to_ban' => ['minecraft:furnace', 'minecraft:blast_furnace'],
        ]);

        $this->getJson('/api/progression/config')
            ->assertOk()
            ->assertExactJson(['banned_recipes' => ['minecraft:furnace', 'minecraft:blast_furnace']]);
    }

    public function test_progression_by_id_shape(): void
    {
        $player = $this->player();

        // The mod reads id, completed_milestones and current_milestone_id (ProgressionData DTO)
        $this->getJson("/api/progression/{$player->active_progression_id}")
            ->assertOk()
            ->assertJsonStructure(['id', 'completed_milestones', 'current_milestone_id'])
            ->assertJsonPath('id', $player->active_progression_id);
    }

    public function test_recipes_list_is_an_array(): void
    {
        $this->getJson('/api/recipes')->assertOk()->assertExactJson([]);
    }

    public function test_launcher_images_only_lists_production_images(): void
    {
        LauncherImage::create(['path' => 'storage/uploads/launcherImages/a.png', 'in_prod' => true, 'player_name' => 'Steve']);
        LauncherImage::create(['path' => 'storage/uploads/launcherImages/b.png', 'in_prod' => false]);

        // The launcher reads item.path (as an <img> src) and item.player_name
        $this->getJson('/api/launcherImages')
            ->assertOk()
            ->assertExactJson([
                ['path' => url('storage/uploads/launcherImages/a.png'), 'player_name' => 'Steve'],
            ]);
    }

    public function test_shop_lists_return_item_arrays(): void
    {
        ShopItem::create([
            'name' => 'Diamond pack', 'description' => 'Some diamonds', 'img_url' => 'images/diamond.png',
            'category_id' => 1, 'real_price' => 100, 'promo_price' => 80, 'is_unique' => false,
            'commands' => ['give {player} minecraft:diamond 1'],
        ]);
        $shape = [['id', 'name', 'description', 'img_url', 'category_id', 'real_price', 'promo_price', 'is_unique']];

        foreach (['/api/shop/bestSallers/5', '/api/shop/newest/5', '/api/shop/sales/5'] as $url) {
            $this->getJson($url)->assertOk()->assertJsonCount(1)->assertJsonStructure($shape);
        }
    }
}
