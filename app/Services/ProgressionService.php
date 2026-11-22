<?php

namespace App\Services;

use App\Models\User;
use App\Models\Stage;
use App\Models\Milestone;
use App\Models\Progression;
use App\Models\MilestoneClosure;
use App\Models\Team;
use App\Progression\ProgressionStateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service class for handling business logic related to player and team progression.
 */
class ProgressionService
{
    public function __construct(private readonly ProgressionStateService $states)
    {
    }

    /**
     * Removes a milestone from a user's active progression.
     * Note: This action will NOT demote the player's stage, even if the completion
     * ratio drops below the threshold, as per the design requirements.
     *
     * @param User $user The user to modify.
     * @param int $milestoneId The ID of the milestone to remove.
     * @return bool True on success.
     */
    public function removeMilestone(User $user, int $milestoneId): bool
    {
        $progression = $user->activeProgression;
        if (!$progression) {
            Log::error("ProgressionService: Could not find active progression for user {$user->uuid}");
            return false;
        }

        return DB::transaction(function () use ($progression, $milestoneId) {
            $progression = Progression::whereKey($progression->id)->lockForUpdate()->firstOrFail();
            $completed = $progression->completed_milestones ?? [];

            if (in_array($milestoneId, $completed)) {
                $progression->completed_milestones = array_values(array_diff($completed, [$milestoneId]));
                $progression->save();
            }
            $this->states->removeLegacyCompletion($progression, $milestoneId);

            return true;
        });
    }

    /**
     * Sets the currently targeted milestone for a user's active progression after validation.
     *
     * @param User $user The user to update.
     * @param int|null $milestoneId The ID of the new target milestone, or null to clear it.
     * @return bool True on success.
     */
    public function setTargetMilestone(User $user, ?int $milestoneId): bool
    {
        $progression = $user->activeProgression;
        if (!$progression) {
            Log::warning("ProgressionService: Could not find active progression for user {$user->uuid} to set target.");
            return false;
        }

        if ($milestoneId === null) {
            $progression->current_milestone_id = null;
            return $progression->save();
        }


        $targetMilestone = Milestone::with('stage')->find($milestoneId);
        if (!$targetMilestone) {
            Log::debug("setTargetMilestone: Target milestone with ID {$milestoneId} not found.");
            return false;
        }

        $maxStage = $progression->maxStage;
        if (!$maxStage) {
            Log::error("ProgressionService: Progression {$progression->id} has an invalid max_stage_id.");
            return false;
        }

        $completedMilestones = $progression->completed_milestones ?? [];

        // The player cannot target a milestone from a stage higher than their maximum unlocked stage.
        if ($targetMilestone->stage->number > $maxStage->number) {
            Log::info("Player {$user->uuid} tried to target milestone {$milestoneId} from stage {$targetMilestone->stage->number}, but max stage is {$maxStage->number}.");
            return false;
        }

        // We collect all milestones that have a direct link to our target.
        $predecessorIds = MilestoneClosure::where('descendant_id', $milestoneId)->pluck('milestone_id')->all();

        if (!empty($predecessorIds)) {
            $intersection = array_intersect($predecessorIds, $completedMilestones);

            if (empty($intersection)) {
                Log::info("Player {$user->uuid} tried to target milestone {$milestoneId} without completing any prerequisite.");
                return false;
            }
        }

        $progression->current_milestone_id = $milestoneId;
        return $progression->save();
    }
}
