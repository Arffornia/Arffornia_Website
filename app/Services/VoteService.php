<?php
namespace App\Services;

use App\Repositories\VoteRepository;



class VoteService {

    private VoteRepository $repository;

    public function __construct(VoteRepository $repository) {
        $this->repository = $repository;
    }
}