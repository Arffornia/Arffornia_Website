<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\TeamRepository;
use App\Repositories\UserRepository;
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
     * Handles the logic when a player joins a team.
     *
     * @param string $playerUuid
     * @param string $teamUuid
     * @param string $teamName
     * @return bool
     */
    public function handlePlayerJoin(string $playerUuid, string $teamUuid, string $teamName): bool
    {
        $user = $this->userRepository->getUserByUuid($playerUuid);
        if (!$user) {
            Log::error("TeamService: User not found for UUID " . $playerUuid);
            return false;
        }

        $team = $this->teamRepository->findOrCreateTeam($teamUuid, $teamName);

        $user->team_id = $team->id;
        $user->active_progression_id = $team->progression_id;
        $user->save();

        return true;
    }

    /**
     * Handles the logic when a player leaves a team.
     *
     * @param string $playerUuid
     * @return bool
     */
    public function handlePlayerLeave(string $playerUuid): bool
    {
        $user = $this->userRepository->getUserByUuid($playerUuid);
        if (!$user || !$user->team_id) {
            Log::warning("TeamService: Player " . $playerUuid . " tried to leave a team but wasn't in one.");
            return false;
        }

        $teamId = $user->team_id;

        // The player switches back to their solo progression
        $user->team_id = null;
        $user->active_progression_id = $user->solo_progression_id;
        $user->save();

        // Check if the team is now empty
        $remainingMembers = User::where('team_id', $teamId)->count();
        if ($remainingMembers === 0) {
            $this->teamRepository->softDelete($teamId);
            Log::info("Team " . $teamId . " has been soft-deleted as it is now empty.");
        }

        return true;
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
