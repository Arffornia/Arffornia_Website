<?php

namespace App\Services;

use App\Models\User;
use App\Models\Stage;
use App\Models\Milestone;
use App\Models\MilestoneClosure;
use Illuminate\Support\Facades\Log;
use App\Repositories\StagesRepository;
use Illuminate\Database\Eloquent\Collection;

class StagesService
{

    private StagesRepository $repository;

    public function __construct(StagesRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get milestone by user
     *
     * @param  User  $user
     * @return Collection<Milestone>
     */
    public function getMilestoneByUsername(User $user)
    {
        return $this->repository->getMilestoneByUsername($user);
    }

    /**
     * Get stage by id
     *
     * @param integer $id
     * @return Stage
     */
    public function getStageById(int $id)
    {
        return $this->repository->getStageById($id);
    }

    /**
     * Get starting stage
     * The starting stage at the beginning stage for each player
     *
     * @return Stage
     */
    public function getStartStage()
    {
        return $this->repository->getStartStage();
    }

    /**
     * Get all stages
     *
     * @return ?Milestone
     */
    public function getMilestoneById(int $milestoneId): ?Milestone
    {
        if ($milestoneId < 0) {
            return null;
        }

        return $this->repository->getMilestoneById($milestoneId);
    }

    /**
     * Create a new milestone.
     *
     * @param array $data
     * @return Milestone
     */
    public function createMilestone(array $data): Milestone
    {
        return $this->repository->createMilestone($data);
    }

    /**
     * Delete a milestone by its ID.
     *
     * @param int $milestoneId
     * @return void
     */
    public function deleteMilestone(int $milestoneId): void
    {
        $this->repository->deleteMilestone($milestoneId);
    }

    /**
     * Create a link between two milestones with validation.
     * It will auto-flip the link if it's created "backwards" (high stage -> low stage)
     * and prevent circular dependencies.
     *
     * @param int $sourceId
     * @param int $targetId
     * @return array Result array with success status, message, and link data.
     */
    public function createLink(int $sourceId, int $targetId): array
    {
        $sourceMilestone = Milestone::with('stage')->find($sourceId);
        $targetMilestone = Milestone::with('stage')->find($targetId);

        if (!$sourceMilestone || !$targetMilestone) {
            return ['success' => false, 'message' => 'One or both milestones not found.'];
        }

        if ($sourceMilestone->stage->number > $targetMilestone->stage->number) {
            $tempId = $sourceId;
            $sourceId = $targetId;
            $targetId = $tempId;
            Log::info("Milestone link creation: Flipped link from {$tempId}->{$sourceId} to {$sourceId}->{$targetId} due to stage order.");
        }

        $link = $this->repository->createLink($sourceId, $targetId);

        if (!$link) {
            return ['success' => false, 'message' => 'Link already exists.'];
        }

        return ['success' => true, 'link' => $link, 'message' => 'Link created successfully.'];
    }

    /**
     * Delete a link between two milestones.
     *
     * @param int $sourceId
     * @param int $targetId
     * @return bool
     */
    public function deleteLink(int $sourceId, int $targetId): bool
    {
        return $this->repository->deleteLink($sourceId, $targetId);
    }

    /**
     * Get the next available stage number.
     *
     * @return int
     */
    public function getNextStageNumber(): int
    {
        $maxNumber = $this->repository->getMaxStageNumber();
        return $maxNumber + 1;
    }

    /**
     * Create a new stage.
     *
     * @param array $data
     * @return Stage
     */
    public function createStage(array $data): Stage
    {
        return $this->repository->createStage($data);
    }

    /**
     * Delete a stage after checking it's not in use.
     *
     * @param Stage $stage
     * @return array
     */
    public function deleteStage(Stage $stage): array
    {
        if ($stage->milestones()->exists()) {
            return ['success' => false, 'message' => 'Cannot delete stage: It still contains milestones.'];
        }

        $this->repository->deleteStage($stage);
        return ['success' => true, 'message' => 'Stage deleted successfully.'];
    }
}
