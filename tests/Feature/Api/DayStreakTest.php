<?php

namespace Tests\Feature\Api;

use App\Models\PendingReward;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;

class DayStreakTest extends ApiTestCase
{
    private function connect(User $player): TestResponse
    {
        return $this->asService('reward_service')->postJson('/api/rewards/day-streak', ['player_uuid' => $player->uuid]);
    }

    private function boxCount(int $rewardId): int
    {
        return PendingReward::findOrFail($rewardId)->contents[0]['count'];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_first_connection_starts_the_streak_and_queues_a_reward(): void
    {
        Carbon::setTestNow('2026-11-09 10:00:00', 'Europe/Paris');
        $player = $this->player(['day_streak' => 0]);

        $response = $this->connect($player)->assertOk()
            ->assertJsonPath('day_streak', 1)
            ->assertJsonPath('is_first_connection_today', true);

        $reward = PendingReward::findOrFail($response->json('reward_id'));
        $this->assertSame('streak', $reward->source);
        $this->assertSame([['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 1]], $reward->contents);
    }

    public function test_second_connection_the_same_day_grants_nothing(): void
    {
        Carbon::setTestNow('2026-11-09 08:00:00');
        $player = $this->player(['day_streak' => 0]);
        $this->connect($player);

        Carbon::setTestNow('2026-11-09 20:00:00');
        $this->connect($player)->assertOk()
            ->assertExactJson(['day_streak' => 1, 'is_first_connection_today' => false, 'reward_id' => null]);

        $this->assertSame(1, PendingReward::where('user_id', $player->id)->count());
    }

    public function test_consecutive_days_extend_the_streak_and_week_two_doubles_the_reward(): void
    {
        $player = $this->player(['day_streak' => 7]);
        $player->forceFill(['last_streak_date' => '2026-11-08'])->save();
        Carbon::setTestNow('2026-11-09 12:00:00');

        $response = $this->connect($player)->assertOk()->assertJsonPath('day_streak', 8);

        $this->assertSame(2, $this->boxCount($response->json('reward_id')));
    }

    public function test_the_reward_is_capped_at_the_last_tier(): void
    {
        $player = $this->player(['day_streak' => 99]);
        $player->forceFill(['last_streak_date' => '2026-11-08'])->save();
        Carbon::setTestNow('2026-11-09 12:00:00');

        $response = $this->connect($player)->assertOk()->assertJsonPath('day_streak', 100);

        $this->assertSame(4, $this->boxCount($response->json('reward_id')));
    }

    public function test_a_missed_day_resets_the_streak_to_one(): void
    {
        $player = $this->player(['day_streak' => 12]);
        $player->forceFill(['last_streak_date' => '2026-11-07'])->save();
        Carbon::setTestNow('2026-11-09 12:00:00');

        $this->connect($player)->assertOk()->assertJsonPath('day_streak', 1);
    }

    public function test_the_day_changes_at_midnight_paris_time_not_utc(): void
    {
        $player = $this->player(['day_streak' => 3]);
        $player->forceFill(['last_streak_date' => '2026-11-09'])->save();
        // 23:30 UTC on the 9th is 00:30 on the 10th in Paris
        Carbon::setTestNow(Carbon::parse('2026-11-09 23:30:00', 'UTC'));

        $this->connect($player)->assertOk()
            ->assertJsonPath('day_streak', 4)
            ->assertJsonPath('is_first_connection_today', true);
    }

    public function test_unknown_player_is_404(): void
    {
        $this->asService('reward_service')
            ->postJson('/api/rewards/day-streak', ['player_uuid' => str_repeat('a', 32)])
            ->assertNotFound();
    }
}
