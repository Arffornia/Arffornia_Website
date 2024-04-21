<?php
namespace App\Services;


use App\Repositories\HomeRepository;
use App\Repositories\StagesRepository;

class HomeService {

    private HomeRepository $repository;

    public function __construct(HomeRepository $repository) {
        $this->repository = $repository;
    }

    
}