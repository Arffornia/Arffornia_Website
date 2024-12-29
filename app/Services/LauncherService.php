<?php
namespace App\Services;

use App\Models\LauncherImage;
use App\Models\LauncherVersion;
use App\Repositories\LauncherRepository;
use Illuminate\Database\Eloquent\Collection;



class LauncherService {

    private LauncherRepository $repository;

    public function __construct(LauncherRepository $repository) {
        $this->repository = $repository;
    }

    /**
     * Get the launcher information in prod
     *
     * @param boolean $devVersion
     * @return LauncherVersion
     */
    public function getLauncherInfo(bool $devVersion) {
        $launcherVersionInfo = $this->repository->getLauncherInfo($devVersion);

        return
        [
            'version'=> $launcherVersionInfo->version,
            'hash' => $launcherVersionInfo->hash,
        ];
    }

    /**
     * Get all launcher images
     *
     * @return Collection<LauncherImage>
     */
    public function getLauncherImages() {
        return $this->repository->getLauncherImages()
            ->pluck('path')
            ->map(function($path) {
                return url($path);
            })
            ->toArray();
    }
}
