<?php
namespace App\Services;

use App\Repositories\LauncherRepository;


class LauncherService {

    private LauncherRepository $repository;

    public function __construct(LauncherRepository $repository) {
        $this->repository = $repository;
    }

    public function getLauncherHash(bool $devVersion) {
        return $this->repository->getLauncherInfo($devVersion)->hash;
    }

    public function getLauncherImages() {
        return $this->repository->getLauncherImages()->pluck('url')->toArray();
    }
}