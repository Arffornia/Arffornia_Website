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

    public function getTopVoters(int $size) {
        return $this->repository->getTopVoters($size);
    }


}