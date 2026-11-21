<?php

namespace Tests\Feature\Api;

use App\Models\PendingReward;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

class RewardApiTest extends ApiTestCase
{
    private const DIAMOND = [['type' => 'item', 'item' => 'minecraft:diamond', 'count' => 3]];

    private function reward(User $player, array $attributes = []): PendingReward
    {
        return PendingReward::create(array_merge([
            'user_id' => $player->id,
            'contents' => self::DIAMOND,
            'status' => PendingReward::STATUS_PENDING,
            'source' => PendingReward::SOURCE_SHOP,
        ], $attributes));
    }

    private function claim(PendingReward|int $reward, User $player, ?string $key = 'key-0123456789abcdef'): TestResponse
    {
        $id = $reward instanceof PendingReward ? $reward->id : $reward;
        $request = $this->asService('reward_service');
        if ($key !== null) {
            $request = $request->withHeader('Idempotency-Key', $key);
        }

        return $request->postJson("/api/rewards/{$id}/claim", ['player_uuid' => $player->uuid]);
    }

    public function test_pending_lists_the_players_deliverable_rewards_oldest_first(): void
    {
        $player = $this->player();
        $first = $this->reward($player);
        $second = $this->reward($player, ['source' => PendingReward::SOURCE_VOTE]);
        $this->reward($player, ['status' => PendingReward::STATUS_CLAIMED]);
        $this->reward($player, ['status' => PendingReward::STATUS_ON_HOLD, 'contents' => null, 'hold_reason' => 'x']);
        $this->reward($this->player());

        $response = $this->asService('reward_service')->getJson('/api/rewards/pending?player_uuid=' . $player->uuid);

        $response->assertOk()
            ->assertJsonPath('rewards.*.id', [$first->id, $second->id])
            ->assertJsonPath('rewards.1.source', 'vote')
            ->assertJsonPath('rewards.0.contents', self::DIAMOND)
            ->assertJsonStructure(['rewards' => [['id', 'source', 'created_at', 'contents']]]);
    }

    public function test_pending_for_an_unknown_player_is_404(): void
    {
        $uuid = str_replace('-', '', (string) Str::uuid());

        $this->asService('reward_service')->getJson('/api/rewards/pending?player_uuid=' . $uuid)->assertNotFound();
    }

    public function test_pending_rejects_a_malformed_uuid(): void
    {
        $this->asService('reward_service')->getJson('/api/rewards/pending?player_uuid=../1')->assertUnprocessable();
    }

    public function test_claim_marks_the_reward_claimed_and_returns_its_contents(): void
    {
        $player = $this->player();
        $reward = $this->reward($player);

        $this->claim($reward, $player)
            ->assertOk()
            ->assertJsonPath('status', 'claimed')
            ->assertJsonPath('reward.contents', self::DIAMOND);

        $reward->refresh();
        $this->assertSame(PendingReward::STATUS_CLAIMED, $reward->status);
        $this->assertSame('key-0123456789abcdef', $reward->claim_key);
        $this->assertNotNull($reward->claimed_at);
    }

    public function test_claim_repeated_with_the_same_key_is_a_successful_replay(): void
    {
        $player = $this->player();
        $reward = $this->reward($player);
        $this->claim($reward, $player)->assertOk();

        $this->claim($reward, $player)->assertOk()->assertJsonPath('status', 'replayed');
    }

    public function test_claim_with_another_key_after_a_claim_is_a_conflict(): void
    {
        $player = $this->player();
        $reward = $this->reward($player);
        $this->claim($reward, $player)->assertOk();

        $this->claim($reward, $player, 'other-key-0123456789')->assertStatus(409)->assertJsonPath('message', 'Reward already claimed.');
    }

    public function test_claim_of_another_players_reward_is_404_and_changes_nothing(): void
    {
        $owner = $this->player();
        $reward = $this->reward($owner);

        $this->claim($reward, $this->player())->assertNotFound();

        $this->assertSame(PendingReward::STATUS_PENDING, $reward->fresh()->status);
    }

    public function test_claim_of_a_reward_on_hold_is_a_conflict(): void
    {
        $player = $this->player();
        $reward = $this->reward($player, ['status' => PendingReward::STATUS_ON_HOLD, 'contents' => null]);

        $this->claim($reward, $player)->assertStatus(409)->assertJsonPath('message', 'Reward cannot be claimed.');
    }

    public function test_claim_of_a_missing_reward_is_404(): void
    {
        $this->claim(999999, $this->player())->assertNotFound();
    }

    public function test_claim_requires_an_idempotency_key(): void
    {
        $player = $this->player();
        $reward = $this->reward($player);

        $this->claim($reward, $player, null)->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $this->claim($reward, $player, 'short')->assertUnprocessable();
    }
}
