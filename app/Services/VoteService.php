<?php

namespace App\Services;

use App\Models\PendingReward;
use App\Models\User;
use App\Repositories\VoteRepository;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VoteService
{
    private VoteRepository $voteRepository;

    public function __construct(VoteRepository $voteRepository)
    {
        $this->voteRepository = $voteRepository;
    }

    /**
     * Verify a vote and grant rewards if successful.
     *
     * @param User $user
     * @param string $siteKey
     * @param Request $request
     * @return array ['success' => bool, 'message' => string]
     */
    public function verifyAndReward(User $user, string $siteKey, Request $request): array
    {
        $siteConfig = config("voting.sites.{$siteKey}");
        if (!$siteConfig) {
            return ['success' => false, 'message' => 'Unknown voting site.'];
        }

        // 1. Internal Cooldown Check
        $lastVote = $this->voteRepository->findLastVote($user, $siteKey);
        if ($lastVote) {
            $cooldownEndsAt = $lastVote->created_at->addHours($siteConfig['cooldown_hours']);
            if (Carbon::now()->isBefore($cooldownEndsAt)) {
                $timeLeft = Carbon::now()->diffForHumans($cooldownEndsAt, true);
                return ['success' => false, 'message' => "You can vote on this site again in {$timeLeft}."];
            }
        }

        // 2. External API Verification using the site-specific verifier
        $verifierClass = $siteConfig['verifier_class'];
        if (!class_exists($verifierClass)) {
            Log::error("Vote Verifier class not found: {$verifierClass}");
            return ['success' => false, 'message' => 'Server configuration error.'];
        }
        /** @var VoteVerifierInterface $verifier */
        $verifier = app($verifierClass);

        if (!$verifier->verify($user, $request)) {
            return ['success' => false, 'message' => 'We could not confirm your vote. Please try again.'];
        }

        // 3. Record Vote and Grant Reward
        $this->voteRepository->recordVote($user, $siteKey, $request->ip());
        $this->grantReward($user);

        return ['success' => true, 'message' => 'Thank you for your vote! Your reward is on its way.'];
    }

    /**
     * Get the top voters for the current month.
     *
     * @param int $size
     * @return Collection
     */
    public function getCurrentMonthTopVoters(int $size): Collection
    {
        return $this->voteRepository->getTopVoters($size, Carbon::now()->startOfMonth());
    }

    /**
     * Get the top voters of all time.
     *
     * @param int $size
     * @return Collection
     */
    public function getAllTimeTopVoters(int $size): Collection
    {
        return $this->voteRepository->getTopVoters($size);
    }

    /**
     * Create a pending reward for the user.
     */
    private function grantReward(User $user): void
    {
        $rewardCommand = "give {$user->name} minecraft:diamond 1"; // Customize reward
        PendingReward::create(['user_id' => $user->id, 'commands' => [$rewardCommand], 'status' => 'pending']);
    }
}
