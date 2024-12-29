<?php
namespace App\Services;

use App\Models\LauncherImage;
use App\Models\LauncherVersion;
use App\Repositories\AdminPanelRepository;
use Illuminate\Support\Collection;


class AdminPanelService {

    private AdminPanelRepository $repository;

    public function __construct(AdminPanelRepository $repository) {
        $this->repository = $repository;
    }

    /**
     * [Obselete] Upload a new launcher version
     *
     * @param string $version
     * @param boolean $in_prod
     * @param mixed $file
     * @return LauncherVersion
     */
    public function uploadNewLauncherVersion(string $version, bool $in_prod, $file) {
        // Store the file in storage\app\public folder
        $fileName = $file->getClientOriginalName();
        $fileName = str_replace(' ', '_', $fileName);
        $filePath = $file->storeAs('uploads/launcherVersions', $fileName, 'public');

        // Get sha1
        $hash = sha1_file($file->getRealPath());

        // Save version file in db
        $this->createNewLauncherVersion($version, $hash, $in_prod, "storage/" . $filePath);
    }

    /**
     * Upload a new launcher image
     *
     * @param boolean $in_prod
     * @param mixed $file
     * @return LauncherImage
     */
    public function uploadNewLauncherImage(bool $in_prod, $file) {
        // Store the file in storage\app\public folder
        $fileName = $file->getClientOriginalName();
        $fileName = str_replace(' ', '_', $fileName);
        $filePath = $file->storeAs('uploads/launcherImages', $fileName, 'public');

        // Save version file in db
        $this->createNewLauncherImage($in_prod, "storage/" . $filePath);
    }

    /**
     * Create a new launcher
     *
     * @param string $version
     * @param string $hash
     * @param boolean $in_prod
     * @param string $filePath
     * @return LauncherVersion
     */
    public function createNewLauncherVersion(string $version, string $hash, bool $in_prod, string $filePath) {
        return $this->repository->createNewLauncherVersion($version, $hash, $in_prod, $filePath);
    }

    /**
     * Create a new launcher
     *
     * @param boolean $in_prod
     * @param string $filePath
     * @return LauncherImage
     */
    public function createNewLauncherImage(bool $in_prod, string $filePath) {
        return $this->repository->createNewLauncherImage($in_prod, $filePath);
    }

    /**
     * Get all launcher version
     *
     * @return Collection<LauncherVersion>
     */
    public function getLauncherVersions() {
        return $this->repository->getLauncherVersions();
    }

    /**
     * Get all launcher images
     *
     * @return Collection<LauncherImage>
     */
    public function getLauncherImages() {
        return $this->repository->getLauncherImages();
    }

}
