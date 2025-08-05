<?php

namespace App\Services;

use App\Models\User;
use App\Models\Stage;
use App\Models\Milestone;
use App\Models\Progression;
use App\Models\MilestoneClosure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service class for handling business logic related to player and team progression.
 */
class ProgressionService
{
    /**
     * The ratio of milestones in a stage that must be completed to unlock the next stage.
     */
    private const UNLOCK_THRESHOLD = 0.60;

    /**
     * Adds a milestone to a user's active progression and checks for a stage-up.
     *
     * @param User $user The user to modify.
     * @param int $milestoneId The ID of the milestone to add.
     * @return bool True on success.
     */
    public function addMilestone(User $user, int $milestoneId): bool
    {
        return DB::transaction(function () use ($user, $milestoneId) {
            $progression = $user->activeProgression;
            if (!$progression) {
                Log::error("ProgressionService: Could not find active progression for user {$user->uuid}");
                return false;
            }

            $completed = $progression->completed_milestones ?? [];

            if (!in_array($milestoneId, $completed)) {
                $completed[] = $milestoneId;
                $progression->completed_milestones = array_unique($completed);
                $progression->save();

                // After adding, check if the player can advance to the next stage.
                $this->checkForStageUp($progression);
            }

            return true;
        });
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

        $completed = $progression->completed_milestones ?? [];

        if (in_array($milestoneId, $completed)) {
            $progression->completed_milestones = array_values(array_diff($completed, [$milestoneId]));
            $progression->save();
        }

        return true;
    }

    /**
     * Checks if a progression has met the criteria to advance to the next stage.
     * If the criteria are met, the progression's max_stage_id is updated.
     * This method will never demote a player.
     *
     * @param Progression $progression The progression entity to check.
     */
    private function checkForStageUp(Progression $progression): void
    {
        $currentStage = Stage::find($progression->max_stage_id);

        if (!$currentStage) {
            Log::error("ProgressionService: Invalid stage ID {$progression->max_stage_id} in progression {$progression->id}");
            return;
        }

        // Check if there is a next stage to advance to
        $nextStage = Stage::where('number', $currentStage->number + 1)->first();
        if (!$nextStage) {
            // Player is already at the highest stage, nothing to do.
            return;
        }

        // Get all milestones belonging to the player's CURRENT max stage
        $milestonesInCurrentStage = Milestone::where('stage_id', $currentStage->id)->get();

        // Count how many of those milestones have been completed
        $completedMilestoneIds = $progression->completed_milestones ?? [];
        $completedCount = $milestonesInCurrentStage->whereIn('id', $completedMilestoneIds)->count();

        // Calculate the completion ratio
        $completionRatio = $completedCount / $milestonesInCurrentStage->count();

        // If the ratio meets or exceeds the threshold, promote the player
        if ($completionRatio >= self::UNLOCK_THRESHOLD) {
            $progression->max_stage_id = $nextStage->id;
            $progression->save();
            Log::info("Progression {$progression->id} advanced to stage {$nextStage->number} (ID: {$nextStage->id}). Ratio: {$completionRatio}");
        }
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
