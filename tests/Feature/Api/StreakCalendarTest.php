<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;

/** Spec 020: `GET /api/players/{uuid}/rewards/streak`, read by the mod's streak crate screen. */
class StreakCalendarTest extends ApiTestCase
{
    private const STREAK_KEY = ['type' => 'key', 'crate' => 'arffornia:streak', 'count' => 1];
    private const VOTE_KEY = ['type' => 'key', 'crate' => 'arffornia:vote', 'count' => 1];
    private const LEGENDARY_KEY = ['type' => 'key', 'crate' => 'arffornia:legendary', 'count' => 1];

    protected function setUp(): void
    {
        parent::setUp();
        config(['rewards.streak.as_keys' => true]);
        Carbon::setTestNow('2026-11-09 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function calendar(User $player): TestResponse
    {
        return $this->asService('reward_service')->getJson("/api/players/{$player->uuid}/rewards/streak");
    }

    private function playerWithStreak(int $days, ?string $lastDate, int $best = 0): User
    {
        $player = $this->player(['day_streak' => $days, 'best_streak' => $best]);
        $player->forceFill(['last_streak_date' => $lastDate])->save();

        return $player;
    }

    public function test_the_calendar_has_the_contract_shape(): void
    {
        $player = $this->playerWithStreak(3, '2026-11-09', 12);

        $this->calendar($player)->assertOk()
            ->assertJsonStructure(['day_streak', 'best_streak', 'last_streak_date', 'today_claimed', 'week' => [['day', 'contents']]])
            ->assertJsonPath('day_streak', 3)
            ->assertJsonPath('best_streak', 12)
            ->assertJsonPath('last_streak_date', '2026-11-09')
            ->assertJsonPath('today_claimed', true)
            ->assertJsonCount(7, 'week');
    }

    public function test_the_week_lists_each_days_reward_from_the_config(): void
    {
        $player = $this->playerWithStreak(3, '2026-11-09');

        $week = $this->calendar($player)->assertOk()->json('week');

        $this->assertSame([1, 2, 3, 4, 5, 6, 7], array_column($week, 'day'));
        $this->assertSame([self::STREAK_KEY], $week[0]['contents']);
        $this->assertSame([self::STREAK_KEY, self::VOTE_KEY], $week[6]['contents']);
    }

    public function test_the_fourth_week_ends_with_the_legendary_key(): void
    {
        $player = $this->playerWithStreak(24, '2026-11-09');

        $week = $this->calendar($player)->assertOk()->json('week');

        $this->assertSame([22, 23, 24, 25, 26, 27, 28], array_column($week, 'day'));
        $this->assertSame([self::STREAK_KEY, self::VOTE_KEY, self::LEGENDARY_KEY], $week[6]['contents']);
    }

    public function test_before_todays_connection_the_week_shows_the_next_day(): void
    {
        $player = $this->playerWithStreak(7, '2026-11-08');

        $response = $this->calendar($player)->assertOk()
            ->assertJsonPath('day_streak', 7)
            ->assertJsonPath('today_claimed', false);

        $this->assertSame(8, $response->json('week.0.day'));
    }

    public function test_a_broken_streak_shows_zero_and_the_first_week(): void
    {
        $player = $this->playerWithStreak(15, '2026-11-07', 15);

        $this->calendar($player)->assertOk()
            ->assertJsonPath('day_streak', 0)
            ->assertJsonPath('best_streak', 15)
            ->assertJsonPath('today_claimed', false)
            ->assertJsonPath('week.0.day', 1);
    }

    public function test_a_new_player_has_an_empty_streak(): void
    {
        $player = $this->playerWithStreak(0, null);

        $this->calendar($player)->assertOk()
            ->assertJsonPath('day_streak', 0)
            ->assertJsonPath('best_streak', 0)
            ->assertJsonPath('last_streak_date', null)
            ->assertJsonPath('week.0.day', 1);
    }

    public function test_with_keys_off_the_week_shows_lucky_boxes(): void
    {
        config(['rewards.streak.as_keys' => false]);
        $player = $this->playerWithStreak(3, '2026-11-09');

        $this->calendar($player)->assertOk()
            ->assertJsonPath('week.0.contents', [['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 1]]);
    }

    public function test_unknown_player_is_404(): void
    {
        $this->asService('reward_service')->getJson('/api/players/' . str_repeat('b', 32) . '/rewards/streak')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_malformed_uuid_is_422(): void
    {
        $this->asService('reward_service')->getJson('/api/players/not-a-uuid/rewards/streak')->assertUnprocessable();
    }
}
