<?php

namespace Tests\Feature;

use App\Models\PendingReward;
use App\Models\ShopItem;
use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConvertLegacyRewardsTest extends TestCase
{
    use RefreshDatabase;

    private User $player;

    protected function setUp(): void
    {
        parent::setUp();
        Stage::factory()->create();
        $this->player = User::factory()->create(['name' => 'Steve']);
    }

    private function legacyReward(array $commands, string $status = 'pending'): PendingReward
    {
        return PendingReward::create(['user_id' => $this->player->id, 'commands' => $commands, 'status' => $status]);
    }

    private function shopItem(array $commands): ShopItem
    {
        return ShopItem::create([
            'name' => 'Kit', 'description' => 'd', 'img_url' => 'i.png', 'category_id' => 0,
            'real_price' => 10, 'promo_price' => 0, 'commands' => $commands,
        ]);
    }

    public function test_convertible_pending_rewards_get_typed_contents(): void
    {
        $reward = $this->legacyReward(['give Steve minecraft:diamond 1', 'say Steve voted!']);

        $this->artisan('rewards:convert-legacy')->assertSuccessful();

        $reward->refresh();
        $this->assertSame(PendingReward::STATUS_PENDING, $reward->status);
        $this->assertSame([['type' => 'item', 'item' => 'minecraft:diamond', 'count' => 1]], $reward->contents);
    }

    public function test_pending_rewards_with_an_unknown_command_are_put_on_hold_with_the_reason(): void
    {
        $reward = $this->legacyReward(['give {player} minecraft:iron_block 16', 'xp add {player} 500 levels']);

        $this->artisan('rewards:convert-legacy')->assertSuccessful();

        $reward->refresh();
        $this->assertSame(PendingReward::STATUS_ON_HOLD, $reward->status);
        $this->assertNull($reward->contents);
        $this->assertStringContainsString('xp add {player} 500 levels', $reward->hold_reason);
    }

    public function test_claimed_rewards_are_left_alone(): void
    {
        $reward = $this->legacyReward(['op {player}'], 'claimed');

        $this->artisan('rewards:convert-legacy')->assertSuccessful();

        $this->assertSame('claimed', $reward->fresh()->status);
    }

    public function test_shop_items_are_converted_or_left_unpurchasable(): void
    {
        $kitten = $this->shopItem(['lp user {player} permission set cosmetic.pet.kitten true', 'say {player} adopted a kitten']);
        $meka = $this->shopItem(['give {player} minecraft:iron_block 16', 'xp add {player} 500 levels']);

        $this->artisan('rewards:convert-legacy')->assertSuccessful();

        $this->assertSame([['type' => 'permission', 'node' => 'cosmetic.pet.kitten', 'value' => true]], $kitten->fresh()->contents);
        $this->assertNull($meka->fresh()->contents);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $reward = $this->legacyReward(['xp add {player} 1 levels']);

        $this->artisan('rewards:convert-legacy --dry-run')->assertSuccessful();

        $this->assertSame(PendingReward::STATUS_PENDING, $reward->fresh()->status);
    }

    public function test_a_scalar_commands_value_is_reported_unsupported_instead_of_crashing(): void
    {
        $reward = $this->legacyReward([]);
        \Illuminate\Support\Facades\DB::table('pending_rewards')->where('id', $reward->id)->update(['commands' => json_encode('op {player}')]);

        $this->artisan('rewards:convert-legacy')->assertSuccessful();

        $this->assertSame(PendingReward::STATUS_ON_HOLD, $reward->fresh()->status);
    }

    public function test_a_held_reward_can_be_released_with_valid_contents(): void
    {
        $reward = $this->legacyReward(['xp add {player} 5 levels']);
        $this->artisan('rewards:convert-legacy');

        $this->artisan('rewards:release', ['id' => $reward->id, 'contents' => '[{"type":"item","item":"minecraft:experience_bottle","count":16}]'])
            ->assertSuccessful();

        $reward->refresh();
        $this->assertSame(PendingReward::STATUS_PENDING, $reward->status);
        $this->assertNull($reward->hold_reason);
        $this->assertSame([['type' => 'item', 'item' => 'minecraft:experience_bottle', 'count' => 16]], $reward->contents);
    }

    public function test_release_refuses_invalid_contents_and_pending_rewards(): void
    {
        $held = $this->legacyReward(['xp add {player} 5 levels']);
        $this->artisan('rewards:convert-legacy');
        $pending = PendingReward::create(['user_id' => $this->player->id, 'contents' => [['type' => 'item', 'item' => 'minecraft:dirt', 'count' => 1]], 'status' => 'pending']);

        $this->artisan('rewards:release', ['id' => $held->id, 'contents' => '[{"type":"command","command":"op x"}]'])->assertFailed();
        $this->artisan('rewards:release', ['id' => $pending->id, 'contents' => '[{"type":"item","item":"minecraft:dirt","count":1}]'])->assertFailed();

        $this->assertSame(PendingReward::STATUS_ON_HOLD, $held->fresh()->status);
    }
}
