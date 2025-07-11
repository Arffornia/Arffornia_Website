<?php

namespace App\Services;

use App\Models\User;
use App\Models\Milestone;
use App\Models\Stage;
use App\Repositories\StagesRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\MilestoneClosure;

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
     * Create a link between two milestones.
     *
     * @param int $sourceId
     * @param int $targetId
     * @return MilestoneClosure|null
     */
    public function createLink(int $sourceId, int $targetId): ?MilestoneClosure
    {
        return $this->repository->createLink($sourceId, $targetId);
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
}
