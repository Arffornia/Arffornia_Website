<?php
namespace App\Services;

use App\Repositories\UserRepository;

class UserService{
    private UserRepository $repository;

    public function __construct(UserRepository $repository) {
        $this->repository = $repository;
    }

    public function getBestUsersByProgressPoints($size) {
        return $this->repository->getBestUsersByProgressPoints($size);
    }

    /**
     * Get user by name
     *
     * @param  string  $name
     * @return
     */
    public function getUserByName(string $name) {
        return $this->repository->getUserByName($name);
    }

    /**
     * Get user by uuid
     *
     * @param  string  $uuid
     * @return
     */
    public function getUserByUuid(string $uuid) {
        return $this->repository->getUserByUuid($uuid);
    }



    public function getTopVoters(int $size) {
        if ($size < 0) {
            $size *= -1;
        }

        if ($size > 25) {
            $size = 25;
        }

        return $this->repository->getTopVoters($size);
    }


}
