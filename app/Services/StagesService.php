<?php

namespace App\Services;

use App\Models\User;
use App\Models\Milestone;
use App\Models\Stage;
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
}
