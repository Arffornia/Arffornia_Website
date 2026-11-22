<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProfileStreakCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Stage::factory()->create();
        Carbon::setTestNow('2026-11-09 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function playerWithStreak(int $days, int $best): User
    {
        $user = User::factory()->create(['day_streak' => $days, 'best_streak' => $best]);
        $user->forceFill(['last_streak_date' => '2026-11-09'])->save();

        return $user;
    }

    public function test_the_profile_shows_the_streak_and_the_weeks_rewards(): void
    {
        config(['rewards.streak.as_keys' => true]);
        $user = $this->playerWithStreak(3, 12);

        $this->withUnencryptedCookie('locale', 'en')->get('/profile/' . $user->name)
            ->assertOk()
            ->assertSeeInOrder(['Daily streak', 'Current streak:', '3 days', 'Best streak:', '12 days'])
            ->assertSeeInOrder(['Day 1', 'Done', 'Day 3', 'Done', 'Day 4', 'Day 7', '1 × Streak crate key', '1 × Vote crate key']);
    }

    public function test_todays_day_is_highlighted(): void
    {
        $user = $this->playerWithStreak(3, 3);

        $content = $this->withUnencryptedCookie('locale', 'en')->get('/profile/' . $user->name)->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="streak-day done today" data-day="3"/', $content);
        $this->assertMatchesRegularExpression('/class="streak-day" data-day="4"/', $content);
    }

    public function test_the_calendar_is_translated_in_french(): void
    {
        config(['rewards.streak.as_keys' => false]);
        $user = $this->playerWithStreak(1, 1);

        $this->withUnencryptedCookie('locale', 'fr')->get('/profile/' . $user->name)
            ->assertOk()
            ->assertSee('Série quotidienne')
            ->assertSee('1 jour')
            ->assertSee('1 × lucky box');
    }
}
