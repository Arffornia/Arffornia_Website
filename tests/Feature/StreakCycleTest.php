<?php

namespace Tests\Feature;

use App\Services\RewardService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Spec 020 D3: the weekly streak cycle, read from config/rewards.php. */
class StreakCycleTest extends TestCase
{
    private const STREAK_KEY = ['type' => 'key', 'crate' => 'arffornia:streak', 'count' => 1];
    private const VOTE_KEY = ['type' => 'key', 'crate' => 'arffornia:vote', 'count' => 1];
    private const LEGENDARY_KEY = ['type' => 'key', 'crate' => 'arffornia:legendary', 'count' => 1];

    public static function cycleDays(): array
    {
        return [
            'day 1 gives a streak key' => [1, [self::STREAK_KEY]],
            'day 6 gives a streak key' => [6, [self::STREAK_KEY]],
            'day 7 adds a vote key' => [7, [self::STREAK_KEY, self::VOTE_KEY]],
            'day 8 starts the next week' => [8, [self::STREAK_KEY]],
            'day 14 adds a vote key again' => [14, [self::STREAK_KEY, self::VOTE_KEY]],
            'day 28 adds a legendary key' => [28, [self::STREAK_KEY, self::VOTE_KEY, self::LEGENDARY_KEY]],
            'day 29 is a plain day' => [29, [self::STREAK_KEY]],
            'day 56 adds a legendary key again' => [56, [self::STREAK_KEY, self::VOTE_KEY, self::LEGENDARY_KEY]],
        ];
    }

    #[DataProvider('cycleDays')]
    public function test_the_weekly_cycle_gives_keys(int $day, array $expected): void
    {
        config(['rewards.streak.as_keys' => true]);

        $contents = app(RewardService::class)->streakContents($day);

        $this->assertSame($expected, $contents);
    }

    public function test_lucky_box_tiers_still_apply_while_keys_are_off(): void
    {
        config(['rewards.streak.as_keys' => false]);

        $contents = app(RewardService::class)->streakContents(8);

        $this->assertSame([['type' => 'lucky_box', 'box' => 'arffornia:streak', 'count' => 2]], $contents);
    }
}
