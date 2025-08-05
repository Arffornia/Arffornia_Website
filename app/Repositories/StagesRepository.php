<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\MilestoneUser;
use App\Models\Milestone;
use App\Models\Stage;
use Illuminate\Database\Eloquent\Collection;
use App\Models\MilestoneClosure;
use Illuminate\Support\Facades\DB;


class StagesRepository
{

    /**
     * Get milestone by user
     *
     * @return Collection<Milestone>
     */
    public function getMilestoneByUsername(User $user)
    {
        return MilestoneUser::where('user_id', $user->id)->get();;
    }

    /**
     * Get Milestone by id
     *
     * @return ?Milestone
     */
    public function getMilestoneById(int $id)
    {
        return Milestone::where('id', $id)->first();
    }

    /**
     * Get stage by id
     *
     * @param integer $id
     * @return Stage
     */
    public function getStageById(int $id)
    {
        return Stage::where('id', $id)->first();
    }

    /**
     * Get starting stage
     * The starting stage at the beginning stage for each player
     *
     * @return Stage
     */
    public function getStartStage()
    {
        return Stage::where('number', 1)->first();
    }

    /**
     * Create a new milestone record.
     *
     * @param array $data Validated data for the new milestone.
     * @return Milestone
     */
    public function createMilestone(array $data): Milestone
    {
        return Milestone::create($data);
    }

    /**
     * Delete a milestone and its associated links.
     *
     * @param int $milestoneId
     * @return void
     */
    public function deleteMilestone(int $milestoneId): void
    {
        DB::transaction(function () use ($milestoneId) {
            // Delete all links to and from this milestone
            MilestoneClosure::where('milestone_id', $milestoneId)
                ->orWhere('descendant_id', $milestoneId)
                ->delete();

            // Delete the milestone itself
            Milestone::destroy($milestoneId);
        });
    }

    /**
     * Create a direct link between two milestones.
     *
     * @param int $sourceId The parent milestone ID.
     * @param int $targetId The child/descendant milestone ID.
     * @return MilestoneClosure|null Returns the new link, or null if it already exists.
     */
    public function createLink(int $sourceId, int $targetId): ?MilestoneClosure
    {
        // Check if the direct link already exists
        $existing = MilestoneClosure::where('milestone_id', $sourceId)
            ->where('descendant_id', $targetId)
            ->first();

        if ($existing) {
            return null; // Link already exists
        }

        // Create the direct link
        $link = new MilestoneClosure();
        $link->milestone_id = $sourceId;
        $link->descendant_id = $targetId;
        $link->save();

        return $link;
    }

    /**
     * Delete a direct link between two milestones.
     *
     * @param int $sourceId The parent milestone ID.
     * @param int $targetId The child/descendant milestone ID.
     * @return bool Returns true if a link was deleted, false otherwise.
     */
    public function deleteLink(int $sourceId, int $targetId): bool
    {
        // Find and delete the direct link
        $deletedCount = MilestoneClosure::where('milestone_id', $sourceId)
            ->where('descendant_id', $targetId)
            ->delete();

        return $deletedCount > 0;
    }

    /**
     * Create a new stage.
     *
     * @param array $data
     * @return Stage
     */
    public function createStage(array $data): Stage
    {
        return Stage::create($data);
    }

    /**
     * Delete a stage.
     *
     * @param Stage $stage
     * @return bool|null
     */
    public function deleteStage(Stage $stage): ?bool
    {
        return $stage->delete();
    }


    /**
     * Get the maximum stage number from the database.
     *
     * @return int
     */
    public function getMaxStageNumber(): int
    {
        return Stage::max('number') ?? 0;
    }
}
