<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Vote;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class VoteRepository
{
    /**
     * Record a new vote in the database.
     *
     * @param User $user
     * @param string $siteKey
     * @param string $ipAddress
     * @return Vote
     */
    public function recordVote(User $user, string $siteKey, string $ipAddress): Vote
    {
        return Vote::create([
            'user_id' => $user->id,
            'site' => $siteKey,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * Find the last vote for a specific user on a specific site.
     *
     * @param User $user
     * @param string $siteKey
     * @return Vote|null
     */
    public function findLastVote(User $user, string $siteKey): ?Vote
    {
        return Vote::where('user_id', $user->id)
            ->where('site', $siteKey)
            ->latest()
            ->first();
    }

    /**
     * Get the top voters within a given date range.
     *
     * @param int $limit
     * @param Carbon|null $startDate
     * @return Collection
     */
    public function getTopVoters(int $limit, Carbon $startDate = null): Collection
    {
        $query = User::query()
            ->selectRaw('users.name, users.uuid, count(votes.id) as votes_count')
            ->join('votes', 'users.id', '=', 'votes.user_id');

        if ($startDate) {
            $query->where('votes.created_at', '>=', $startDate);
        }

        return $query->groupBy('users.id', 'users.name', 'users.uuid')
            ->orderByDesc('votes_count')
            ->limit($limit)
            ->get();
    }
}
