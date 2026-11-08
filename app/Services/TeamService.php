<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepository;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamService
{
    protected TeamRepository $teamRepository;
    protected UserRepository $userRepository;

    public function __construct(TeamRepository $teamRepository, UserRepository $userRepository)
    {
        $this->teamRepository = $teamRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Sets a player's team to what the game server observed at $observedAt. Idempotent: replaying
     * the call, or receiving an older observation after a newer one, leaves the state unchanged.
     * Returns false only when the player does not exist.
     */
    public function syncPlayerTeam(string $playerUuid, ?string $teamUuid, ?string $teamName, CarbonInterface $observedAt): bool
    {
        return DB::transaction(function () use ($playerUuid, $teamUuid, $teamName, $observedAt) {
            $user = User::where('uuid', $playerUuid)->lockForUpdate()->first();
            if (!$user) {
                Log::error("TeamService: User not found for UUID " . $playerUuid);
                return false;
            }

            if ($user->team_synced_at !== null && $observedAt->lessThanOrEqualTo($user->team_synced_at)) {
                return true;
            }

            $previousTeamId = $user->team_id;

            if ($teamUuid === null) {
                $user->team_id = null;
                $user->active_progression_id = $user->solo_progression_id;
            } else {
                $team = $this->teamRepository->findOrCreateTeam($teamUuid, $teamName ?? $teamUuid);
                $user->team_id = $team->id;
                $user->active_progression_id = $team->progression_id;
            }

            $user->team_synced_at = $observedAt;
            $user->save();

            if ($previousTeamId !== null && $previousTeamId !== $user->team_id) {
                $this->softDeleteIfEmpty($previousTeamId);
            }

            return true;
        });
    }

    public function handlePlayerJoin(string $playerUuid, string $teamUuid, string $teamName): bool
    {
        return $this->syncPlayerTeam($playerUuid, $teamUuid, $teamName, now());
    }

    public function handlePlayerLeave(string $playerUuid): bool
    {
        return $this->syncPlayerTeam($playerUuid, null, null, now());
    }

    private function softDeleteIfEmpty(string $teamId): void
    {
        if (User::where('team_id', $teamId)->exists()) {
            return;
        }

        $this->teamRepository->softDelete($teamId);
        Log::info("Team " . $teamId . " has been soft-deleted as it is now empty.");
    }

    /**
     * Handles disbanding a team.
     *
     * @param string $teamUuid
     * @return bool
     */
    public function handleTeamDisband(string $teamUuid): bool
    {
        $team = $this->teamRepository->findByUuid($teamUuid);
        if (!$team) {
            return false;
        }

        // Reassign all members to their solo progression
        User::where('team_id', $team->id)->chunkById(100, function ($members) {
            foreach ($members as $member) {
                $member->team_id = null;
                $member->active_progression_id = $member->solo_progression_id;
                $member->save();
            }
        });

        // Soft delete the team
        return $this->teamRepository->softDelete($team->id);
    }
}
