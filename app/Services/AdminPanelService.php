<?php

namespace App\Services;

use App\Models\LauncherImage;
use App\Repositories\AdminPanelRepository;
use Illuminate\Support\Collection;


class AdminPanelService
{

    private AdminPanelRepository $repository;

    public function __construct(AdminPanelRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Upload a new launcher image
     *
     * @param boolean $in_prod
     * @param mixed $file
     * @return LauncherImage
     */
    public function uploadNewLauncherImage(bool $in_prod, $file, ?string $playerName)
    {
        // Store the file in storage\app\public folder
        $fileName = $file->getClientOriginalName();
        $fileName = str_replace(' ', '_', $fileName);
        $filePath = $file->storeAs('uploads/launcherImages', $fileName, 'public');

        // Save version file in db
        $this->createNewLauncherImage($in_prod, "storage/" . $filePath, $playerName);
    }

    /**
     * Create a new launcher
     *
     * @param boolean $in_prod
     * @param string $filePath
     * @return LauncherImage
     */
    public function createNewLauncherImage(bool $in_prod, string $filePath, ?string $playerName)
    {
        return $this->repository->createNewLauncherImage($in_prod, $filePath, $playerName);
    }

    /**
     * Get all launcher images
     *
     * @return Collection<LauncherImage>
     */
    public function getLauncherImages()
    {
        return $this->repository->getLauncherImages();
    }
}
