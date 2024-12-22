<?php
namespace App\Services;

use App\Models\User;
use App\Models\Milestone;
use App\Repositories\StagesRepository;
use Illuminate\Database\Eloquent\Collection;

class StagesService {

    private StagesRepository $repository;

    public function __construct(StagesRepository $repository) {
        $this->repository = $repository;
    }

    /**
     * Get milestone by user
     *
     * @param  User  $user
     * @return Collection<Milestone>
     */
    public function getMilestoneByUsername(User $user) {
        return $this->repository->getMilestoneByUsername($user);
    }

    /**
     * Get stage by id
     *
     * @param integer $id
     * @return Stage
     */
    public function getStageById(int $id) {
        return $this->repository->getStageById($id);
    }

    /**
     * Get starting stage
     * The starting stage at the beginning stage for each player
     *
     * @return Stage
     */
    public function getStartStage() {
        return $this->repository->getStartStage();
    }

}
