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
     * Get user by name
     *
     * @param  string  $name
     * @return User
     */
    public function getUserByName(string $name) {
        $this->repository->getUserByName($name);
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
}