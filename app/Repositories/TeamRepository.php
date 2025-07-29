<?php

namespace App\Repositories;

use App\Models\Team;
use App\Models\Stage;
use App\Models\Progression;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class TeamRepository
{
    /**
     * Finds a team by its UUID or creates it if it doesn't exist.
     * Creation includes a new progression entity.
     *
     * @param string $teamUuid The UUID of the FTB team.
     * @param string $teamName The name of the team.
     * @return Team
     */
    public function findOrCreateTeam(string $teamUuid, string $teamName): Team
    {
        // Use restore() to undo a soft delete if the team is being recreated.
        $team = Team::withTrashed()->where('id', $teamUuid)->first();

        if ($team) {
            if ($team->trashed()) {
                $team->restore();
            }

            return $team;
        }

        // If the team doesn't exist at all, create it within a transaction
        return DB::transaction(function () use ($teamUuid, $teamName) {
            $startStage = Stage::where('number', 1)->firstOrFail();

            // 1. Create the progression for the team
            $progression = Progression::create([
                'max_stage_id' => $startStage->id,
                'completed_milestones' => [],
            ]);

            // 2. Create the team
            $newTeam = Team::create([
                'id' => $teamUuid,
                'name' => $teamName,
                'progression_id' => $progression->id,
            ]);

            return $newTeam;
        });
    }

    /**
     * Finds a team by its UUID.
     *
     * @param string $teamUuid
     * @return Team|null
     */
    public function findByUuid(string $teamUuid): ?Team
    {
        return Team::find($teamUuid);
    }

    /**
     * Performs a soft delete on a team.
     *
     * @param string $teamUuid
     * @return bool
     */
    public function softDelete(string $teamUuid): bool
    {
        $team = $this->findByUuid($teamUuid);
        if ($team) {
            return $team->delete();
        }
        return false;
    }
}
