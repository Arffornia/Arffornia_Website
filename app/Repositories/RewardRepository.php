<?php

namespace App\Repositories;

use App\Models\PendingReward;
use App\Models\User;
use Illuminate\Support\Collection;

class RewardRepository
{
    public function findUserByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)->first();
    }

    public function lockUser(int $userId): User
    {
        return User::whereKey($userId)->lockForUpdate()->firstOrFail();
    }

    /** @return Collection<int, PendingReward> */
    public function pendingFor(User $user, int $limit): Collection
    {
        return PendingReward::where('user_id', $user->id)
            ->where('status', PendingReward::STATUS_PENDING)
            ->whereNotNull('contents')
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function lockReward(int $rewardId): ?PendingReward
    {
        return PendingReward::whereKey($rewardId)->lockForUpdate()->first();
    }
}
