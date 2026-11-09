<?php

namespace App\Services;

use App\Models\PendingReward;
use App\Models\User;
use App\Repositories\RewardRepository;
use App\Rewards\RewardCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RewardService
{
    public const CLAIM_OK = 'claimed';
    public const CLAIM_REPLAYED = 'replayed';
    public const CLAIM_NOT_FOUND = 'not_found';
    public const CLAIM_ALREADY_CLAIMED = 'already_claimed';
    public const CLAIM_NOT_CLAIMABLE = 'not_claimable';

    public function __construct(private readonly RewardRepository $rewards)
    {
    }

    public function findPlayer(string $uuid): ?User
    {
        return $this->rewards->findUserByUuid($uuid);
    }

    /** @return Collection<int, PendingReward> */
    public function pendingFor(User $user): Collection
    {
        return $this->rewards->pendingFor($user, (int) config('rewards.pending_page_size', 50));
    }

    /**
     * Marks a reward claimed. Repeating the call with the same key is a no-op that succeeds, so the game server can
     * retry after a lost response without delivering twice.
     *
     * @return array{0: string, 1: PendingReward|null}
     */
    public function claim(User $player, int $rewardId, string $claimKey): array
    {
        return DB::transaction(function () use ($player, $rewardId, $claimKey) {
            $reward = $this->rewards->lockReward($rewardId);

            if ($reward === null || $reward->user_id !== $player->id) {
                return [self::CLAIM_NOT_FOUND, null];
            }
            if ($reward->status === PendingReward::STATUS_CLAIMED) {
                return $reward->claim_key !== null && hash_equals($reward->claim_key, $claimKey)
                    ? [self::CLAIM_REPLAYED, $reward]
                    : [self::CLAIM_ALREADY_CLAIMED, null];
            }
            if ($reward->status !== PendingReward::STATUS_PENDING || empty($reward->contents)) {
                return [self::CLAIM_NOT_CLAIMABLE, null];
            }

            $reward->forceFill([
                'status' => PendingReward::STATUS_CLAIMED,
                'claim_key' => $claimKey,
                'claimed_at' => now(),
            ])->save();

            return [self::CLAIM_OK, $reward];
        });
    }

    /**
     * Records the player's connection for the daily streak. The first connection of a day extends the streak (or
     * restarts it after a missed day) and queues that day's reward.
     *
     * @return array{day_streak: int, is_first_connection_today: bool, reward_id: int|null}
     */
    public function recordDailyConnection(User $player): array
    {
        $timezone = (string) config('rewards.streak.timezone');
        $today = CarbonImmutable::now($timezone)->startOfDay();

        return DB::transaction(function () use ($player, $today) {
            $user = $this->rewards->lockUser($player->id);
            $last = $user->last_streak_date?->format('Y-m-d');

            if ($last === $today->format('Y-m-d')) {
                return ['day_streak' => (int) $user->day_streak, 'is_first_connection_today' => false, 'reward_id' => null];
            }

            $streak = $last === $today->subDay()->format('Y-m-d') ? (int) $user->day_streak + 1 : 1;
            $user->forceFill(['day_streak' => $streak, 'last_streak_date' => $today->format('Y-m-d')])->save();

            $reward = PendingReward::create([
                'user_id' => $user->id,
                'contents' => $this->streakContents($streak),
                'commands' => [],
                'status' => PendingReward::STATUS_PENDING,
                'source' => PendingReward::SOURCE_STREAK,
            ]);

            return ['day_streak' => $streak, 'is_first_connection_today' => true, 'reward_id' => $reward->id];
        });
    }

    /** @return list<array<string, mixed>> */
    public function streakContents(int $streak): array
    {
        $tier = collect(config('rewards.streak.tiers'))
            ->filter(fn (array $tier) => $tier['min_days'] <= $streak)
            ->sortBy('min_days')
            ->last();

        if ($tier === null) {
            throw new RuntimeException("rewards.streak.tiers has no tier for a streak of {$streak} days; the first tier must start at 1.");
        }

        return RewardCatalog::validate($tier['contents']);
    }
}
