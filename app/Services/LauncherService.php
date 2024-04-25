<?php
namespace App\Services;

use App\Repositories\LauncherRepository;


class LauncherService {

    private LauncherRepository $repository;

    public function __construct(LauncherRepository $repository) {
        $this->repository = $repository;
    }

    public function getLauncherInfo(bool $devVersion) {
        $launcherVersionInfo = $this->repository->getLauncherInfo($devVersion);

        return 
        [
            'version'=> $launcherVersionInfo->version,
            'hash' => $launcherVersionInfo->hash,
        ];
    }

    public function getLauncherImages() {
        return $this->repository->getLauncherImages()->pluck('url')->toArray();
    }
}